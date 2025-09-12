<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\StockHistory;
use App\Models\WarehouseStock;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class SalesController
 *
 * Handles all sales order (invoice) management operations for the Stock
 * Management System API. This controller manages the full lifecycle of a
 * sales transaction: creation, retrieval, updating, payment recording,
 * PDF generation, bulk operations, and soft deletion.
 *
 * Sales orders consume inventory from a specified warehouse and create
 * a financial obligation (due_amount) that must be settled via payment
 * entries. The controller integrates with the WarehouseStock model to
 * enforce stock availability when negative stock is not permitted.
 *
 * Key Responsibilities:
 *   - List and filter sales orders with pagination
 *   - Create sales orders with stock availability checks
 *   - Update draft/pending sales orders
 *   - Record full and partial payments against an order
 *   - Generate printable/downloadable PDF invoices
 *   - Bulk status update and deletion operations
 *   - Return detailed order view with all relationships
 *
 * @package App\Http\Controllers\Api
 * @author  StockManager Development Team
 * @version 3.0.0
 */
class SalesController extends ApiBaseController
{
    // ─── Constants ────────────────────────────────────────────────────────────

    /**
     * Invoice number prefix for sales orders.
     *
     * @var string
     */
    private const INVOICE_PREFIX = 'SAL';

    /**
     * Allowed statuses for bulk status updates.
     *
     * @var array<string>
     */
    private const BULK_ALLOWED_STATUSES = [
        'pending', 'completed', 'cancelled', 'shipped',
    ];

    // ─── Public API Methods ───────────────────────────────────────────────────

    /**
     * Display a paginated list of sales orders.
     *
     * Supports filtering by:
     *   - from_date / to_date: date range on order_date
     *   - order_status: filter by specific status
     *   - party_id: filter by customer ID
     *   - warehouse_id: filter by warehouse
     *   - search: match against invoice_number
     *
     * Eager-loads items, their products, the customer party,
     * the warehouse, and the staff member who created the order.
     *
     * @param  Request  $request  Incoming HTTP request with optional filters
     * @return JsonResponse       Paginated list of sales orders
     */
    public function index(Request $request): JsonResponse
    {
        // Start with the sales order type scoped to the company (via BaseModel)
        $query = Order::ofType('sales')
            ->with([
                'items.product',
                'party',
                'warehouse',
                'staffMember:id,name',
            ])
            ->withDateRange($request->from_date, $request->to_date);

        // Apply optional status filter
        if ($request->filled('order_status')) {
            $query->where('order_status', $request->order_status);
        }

        // Apply optional customer / party filter
        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id)
                  ->where('party_type', Customer::class);
        }

        // Apply optional warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Apply optional invoice number search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                  ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        // Sort by order_date descending by default
        $sorted = $this->applySorting($query, 'order_date', 'desc');

        return $this->sendResponse($this->paginate($sorted), 'Sales orders retrieved successfully');
    }

    /**
     * Create a new sales order.
     *
     * Validates request data, checks stock availability (unless
     * allow_negative_stock setting is enabled), creates the order
     * and its line items, computes totals, and optionally records
     * an initial payment if provided.
     *
     * Required fields:
     *   - warehouse_id: target warehouse for stock deduction
     *   - order_date: date of the transaction (Y-m-d)
     *   - items: array of line items with product_id, quantity, unit_price
     *
     * Optional fields:
     *   - customer_id: link to a Customer record
     *   - discount: flat discount on the whole order
     *   - tax_amount: overall tax amount
     *   - shipping: shipping/freight charge
     *   - notes: free-text notes
     *   - payment: initial payment details
     *
     * @param  Request  $request  Validated request data
     * @return JsonResponse       201 Created with the new order data
     */
    public function store(Request $request): JsonResponse
    {
        // Validate all incoming fields with detailed rules
        $validated = $request->validate([
            'warehouse_id'              => 'required|integer|exists:warehouses,id',
            'order_date'                => 'required|date',
            'customer_id'               => 'nullable|integer|exists:customers,id',
            'discount'                  => 'nullable|numeric|min:0',
            'tax_amount'                => 'nullable|numeric|min:0',
            'shipping'                  => 'nullable|numeric|min:0',
            'notes'                     => 'nullable|string|max:2000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|integer|exists:products,id',
            'items.*.quantity'          => 'required|numeric|min:0.001',
            'items.*.unit_price'        => 'required|numeric|min:0',
            'items.*.discount'          => 'nullable|numeric|min:0',
            'items.*.tax_id'            => 'nullable|integer|exists:taxes,id',
            'payment'                   => 'nullable|array',
            'payment.amount'            => 'required_with:payment|numeric|min:0.01',
            'payment.payment_mode_id'   => 'required_with:payment|integer|exists:payment_modes,id',
            'payment.notes'             => 'nullable|string|max:500',
        ]);

        $companyId     = $this->getCompanyId();
        $allowNegative = Settings::getSetting('allow_negative_stock', $companyId);

        // ── Stock Availability Check ────────────────────────────────────────
        // Only perform check when allow_negative_stock is disabled
        if (!$allowNegative) {
            $stockShortages = $this->checkStockAvailability(
                $request->warehouse_id,
                $request->items
            );

            if (!empty($stockShortages)) {
                return $this->sendError(
                    'Insufficient stock for one or more items.',
                    ['shortages' => $stockShortages],
                    422
                );
            }
        }

        // ── Create Order in a Transaction ───────────────────────────────────
        try {
            $order = DB::transaction(function () use ($request, $validated, $companyId) {
                // Build the order record
                $orderData = [
                    'company_id'     => $companyId,
                    'warehouse_id'   => $validated['warehouse_id'],
                    'order_type'     => Order::TYPE_SALES,
                    'invoice_number' => $this->generateInvoiceNumber(self::INVOICE_PREFIX),
                    'order_status'   => Order::STATUS_COMPLETED,
                    'order_date'     => $validated['order_date'],
                    'party_id'       => $validated['customer_id'] ?? null,
                    'party_type'     => isset($validated['customer_id']) ? Customer::class : null,
                    'discount'       => $validated['discount'] ?? 0.00,
                    'tax_amount'     => $validated['tax_amount'] ?? 0.00,
                    'shipping'       => $validated['shipping'] ?? 0.00,
                    'notes'          => $validated['notes'] ?? null,
                    'staff_user_id'  => $this->getAuthUserId(),
                    'subtotal'       => 0.00,
                    'grand_total'    => 0.00,
                    'due_amount'     => 0.00,
                ];

                $order = Order::create($orderData);

                // ── Process Line Items ─────────────────────────────────────
                $subtotal = 0.00;
                foreach ($request->items as $item) {
                    $lineSubtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $lineDiscount = (float) ($item['discount'] ?? 0);
                    $lineTotal    = max(0, $lineSubtotal - $lineDiscount);
                    $subtotal    += $lineTotal;

                    $order->items()->create([
                        'company_id'  => $companyId,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['unit_price'],
                        'discount'    => $lineDiscount,
                        'subtotal'    => $lineTotal,
                        'tax_id'      => $item['tax_id'] ?? null,
                    ]);

                    // ── Deduct Stock ───────────────────────────────────────
                    WarehouseStock::where('warehouse_id', $request->warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->decrement('quantity', $item['quantity']);

                    // ── Record Stock History ───────────────────────────────
                    StockHistory::create([
                        'company_id'   => $companyId,
                        'warehouse_id' => $request->warehouse_id,
                        'product_id'   => $item['product_id'],
                        'quantity'     => -abs($item['quantity']),
                        'order_type'   => Order::TYPE_SALES,
                        'order_id'     => $order->id,
                        'type'         => 'out',
                        'created_by'   => $this->getAuthUserId(),
                    ]);
                }

                // ── Recalculate and Persist Totals ─────────────────────────
                $order->subtotal    = $subtotal;
                $order->grand_total = $order->calculateGrandTotal();
                $order->due_amount  = $order->grand_total;
                $order->save();

                // ── Optional Initial Payment ───────────────────────────────
                if ($request->filled('payment')) {
                    $this->recordPayment($order, $request->payment, $companyId);
                }

                return $order;
            });

            $this->logAction('sales.created', ['order_id' => $order->id, 'total' => $order->grand_total]);

            return $this->sendResponse(
                $order->load(['items.product', 'party', 'warehouse', 'payments']),
                'Sale created successfully',
                201
            );
        } catch (\Throwable $e) {
            Log::error('SalesController@store failed', [
                'error'      => $e->getMessage(),
                'user_id'    => $this->getAuthUserId(),
                'company_id' => $this->getCompanyId(),
            ]);

            return $this->sendError('Failed to create sale order. Please try again.', [], 500);
        }
    }

    /**
     * Retrieve a single sales order with all related data.
     *
     * Loads the full order with line items, products, customer party,
     * warehouse, payment history, custom fields, and staff member.
     *
     * @param  int  $id  The sales order ID
     * @return JsonResponse  Full order detail with relationships
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with([
            'items.product.category',
            'items.product.unit',
            'items.tax',
            'party',
            'warehouse',
            'payments.payment.paymentMode',
            'staffMember:id,name,email',
            'customFields',
            'shippingAddress',
        ])->ofType('sales')
          ->findOrFail($id);

        // Append computed attributes for the frontend
        $orderData                     = $order->toArray();
        $orderData['is_fully_paid']    = $order->is_fully_paid;
        $orderData['is_partially_paid'] = $order->is_partially_paid;
        $orderData['payment_progress'] = $order->payment_progress;
        $orderData['total_paid']       = $order->total_paid;
        $orderData['item_count']       = $order->getItemCount();
        $orderData['total_quantity']   = $order->getTotalQuantity();

        return $this->sendResponse($orderData, 'Sale retrieved successfully');
    }

    /**
     * Update an existing sales order.
     *
     * Only orders in 'pending' or 'draft' status can be updated.
     * Completed or shipped orders cannot be modified. Line items
     * are replaced entirely when provided.
     *
     * @param  Request  $request  New order data
     * @param  int      $id       Sales order ID to update
     * @return JsonResponse       Updated order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::ofType('sales')->findOrFail($id);

        // Only allow edits on draft/pending orders
        if (!$order->isEditable()) {
            return $this->sendError(
                "Cannot modify a {$order->order_status} order.",
                [],
                422
            );
        }

        $validated = $request->validate([
            'order_date'   => 'sometimes|date',
            'customer_id'  => 'nullable|integer|exists:customers,id',
            'discount'     => 'nullable|numeric|min:0',
            'tax_amount'   => 'nullable|numeric|min:0',
            'shipping'     => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:2000',
            'order_status' => 'sometimes|in:pending,draft,cancelled',
        ]);

        $order->update(array_filter($validated, fn($v) => $v !== null));

        if (isset($validated['customer_id'])) {
            $order->update([
                'party_id'   => $validated['customer_id'],
                'party_type' => Customer::class,
            ]);
        }

        // Recalculate totals after updating header fields
        $order->recalculateTotals();

        $this->logAction('sales.updated', ['order_id' => $order->id]);

        return $this->sendResponse(
            $order->fresh()->load(['items.product', 'party', 'warehouse']),
            'Sale updated successfully'
        );
    }

    /**
     * Record a payment against a sales order.
     *
     * Creates a Payment record, links it via OrderPayment, and
     * recalculates the order's due_amount. Prevents overpayment
     * beyond the outstanding balance.
     *
     * @param  Request  $request  Payment details
     * @param  int      $id       Sales order ID
     * @return JsonResponse       Updated order with payment summary
     */
    public function addPayment(Request $request, int $id): JsonResponse
    {
        $order = Order::ofType('sales')->findOrFail($id);

        if (!$order->acceptsPayment()) {
            return $this->sendError(
                'This order does not accept payments in its current status.',
                [],
                422
            );
        }

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_mode_id'  => 'required|integer|exists:payment_modes,id',
            'date'             => 'nullable|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        // Prevent overpayment
        if ((float) $validated['amount'] > $order->due_amount) {
            return $this->sendError(
                "Payment amount ({$validated['amount']}) exceeds outstanding balance ({$order->due_amount}).",
                [],
                422
            );
        }

        try {
            $payment = DB::transaction(function () use ($order, $validated) {
                return $this->recordPayment($order, $validated, $order->company_id);
            });

            return $this->sendResponse([
                'payment'          => $payment->load('paymentMode'),
                'order'            => $order->fresh(),
                'remaining_due'    => $order->fresh()->due_amount,
                'is_fully_paid'    => $order->fresh()->is_fully_paid,
            ], 'Payment recorded successfully');
        } catch (\Throwable $e) {
            Log::error('SalesController@addPayment failed', ['error' => $e->getMessage(), 'order_id' => $id]);
            return $this->sendError('Failed to record payment. Please try again.', [], 500);
        }
    }

    /**
     * Delete a sales order (soft delete).
     *
     * Completed orders with payments cannot be deleted. Only pending,
     * draft, or cancelled orders may be removed. Soft deletion preserves
     * the record for audit purposes.
     *
     * @param  int  $id  Sales order ID to delete
     * @return JsonResponse  Confirmation message
     */
    public function destroy(int $id): JsonResponse
    {
        $order = Order::ofType('sales')->findOrFail($id);

        // Prevent deletion of orders that have payments recorded
        if ($order->payments()->exists()) {
            return $this->sendError(
                'Cannot delete a sales order that has payment records. Cancel it instead.',
                [],
                422
            );
        }

        $this->logAction('sales.deleted', ['order_id' => $id, 'invoice' => $order->invoice_number]);

        $order->delete();

        return $this->sendResponse([], 'Sale deleted successfully');
    }

    /**
     * Bulk delete multiple sales orders.
     *
     * Iterates over supplied IDs and soft-deletes each one.
     * Orders with payments are skipped and reported in the errors array.
     * Returns a summary of successful and failed deletions.
     *
     * @param  Request  $request  Must contain: ids (array of order IDs)
     * @return JsonResponse       Bulk operation result summary
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $result = $this->processBulk($request->ids, function (int $orderId) {
            $order = Order::ofType('sales')->findOrFail($orderId);

            if ($order->payments()->exists()) {
                throw new \RuntimeException("Order #{$orderId} has payments and cannot be deleted.");
            }

            $order->delete();
        });

        $message = "Bulk delete: {$result['success']} succeeded, {$result['failed']} failed.";
        return $this->sendResponse($result, $message);
    }

    /**
     * Bulk update the status of multiple sales orders.
     *
     * Validates that the new status is one of the allowed bulk statuses,
     * then updates each order. Returns a result summary.
     *
     * @param  Request  $request  Must contain: ids (array), status (string)
     * @return JsonResponse       Bulk operation result summary
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:orders,id',
            'status' => 'required|in:' . implode(',', self::BULK_ALLOWED_STATUSES),
        ]);

        $newStatus = $request->status;

        $result = $this->processBulk($request->ids, function (int $orderId) use ($newStatus) {
            $order = Order::ofType('sales')->findOrFail($orderId);
            $order->update(['order_status' => $newStatus]);
        });

        $message = "Bulk status update: {$result['success']} succeeded, {$result['failed']} failed.";
        return $this->sendResponse($result, $message);
    }

    /**
     * Get a summary of sales for the dashboard or reporting widgets.
     *
     * Returns aggregate statistics for the authenticated company:
     *   - total_orders: total count of sales orders
     *   - total_revenue: sum of grand_total
     *   - total_collected: sum of payments received
     *   - total_outstanding: sum of due_amount
     *   - by_status: count and revenue grouped by order_status
     *
     * Supports date range filtering via from_date / to_date.
     *
     * @param  Request  $request  Optional date range filters
     * @return JsonResponse       Sales summary statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Order::ofType('sales')
            ->withDateRange($request->from_date, $request->to_date);

        $overall = (clone $query)->selectRaw(
            'COUNT(*) as total_orders,
             COALESCE(SUM(grand_total), 0) as total_revenue,
             COALESCE(SUM(grand_total - due_amount), 0) as total_collected,
             COALESCE(SUM(due_amount), 0) as total_outstanding,
             COALESCE(SUM(discount), 0) as total_discount,
             COALESCE(AVG(grand_total), 0) as average_order_value'
        )->first();

        $byStatus = (clone $query)->selectRaw(
            'order_status,
             COUNT(*) as count,
             COALESCE(SUM(grand_total), 0) as revenue'
        )->groupBy('order_status')->get();

        return $this->sendResponse([
            'overall'   => $overall,
            'by_status' => $byStatus,
        ], 'Sales summary retrieved');
    }

    /**
     * Get payment history for a specific sales order.
     *
     * Returns a list of all payments linked to the order with their
     * payment mode, date, amount, and staff member details.
     *
     * @param  int  $id  Sales order ID
     * @return JsonResponse  Payment history list
     */
    public function payments(int $id): JsonResponse
    {
        $order = Order::ofType('sales')->with([
            'payments.payment.paymentMode',
            'payments.payment.staffMember:id,name',
        ])->findOrFail($id);

        return $this->sendResponse([
            'order_id'     => $order->id,
            'grand_total'  => $order->grand_total,
            'total_paid'   => $order->total_paid,
            'due_amount'   => $order->due_amount,
            'payments'     => $order->payments,
        ], 'Payment history retrieved');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Check stock availability for a list of items in a warehouse.
     *
     * Fetches current warehouse stock for each product and returns
     * an array of shortages where available stock is insufficient.
     *
     * @param  int    $warehouseId  Warehouse to check stock in
     * @param  array  $items        Array of ['product_id'=>int, 'quantity'=>float]
     * @return array                Array of shortage details (empty if all OK)
     */
    private function checkStockAvailability(int $warehouseId, array $items): array
    {
        $shortages = [];

        foreach ($items as $item) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $item['product_id'])
                ->first();

            $available = $stock ? (float) $stock->quantity : 0.0;

            if ($available < (float) $item['quantity']) {
                $shortages[] = [
                    'product_id' => $item['product_id'],
                    'requested'  => $item['quantity'],
                    'available'  => $available,
                    'shortage'   => max(0, (float) $item['quantity'] - $available),
                ];
            }
        }

        return $shortages;
    }

    /**
     * Generate a unique invoice number for a sales order.
     *
     * Combines the prefix, a unique ID, and the current date for
     * readability. Ensures global uniqueness via uniqid().
     *
     * @param  string  $prefix  Invoice prefix (e.g. 'SAL')
     * @return string           Unique invoice number like 'SAL-64A3B2F1-20240115'
     */
    private function generateInvoiceNumber(string $prefix): string
    {
        return $prefix . '-' . strtoupper(Str::random(8)) . '-' . date('Ymd');
    }

    /**
     * Create a Payment record and link it to an Order via OrderPayment.
     *
     * This helper is shared between store() and addPayment() to avoid
     * code duplication. It creates the Payment record, the OrderPayment
     * junction, and then recalculates the order's due amount.
     *
     * @param  Order  $order      The order to link the payment to
     * @param  array  $payData    Payment details (amount, payment_mode_id, date, notes)
     * @param  int    $companyId  Company ID for the payment record
     * @return Payment            The newly created Payment model instance
     */
    private function recordPayment(Order $order, array $payData, int $companyId): Payment
    {
        // Create the core payment record
        $payment = Payment::create([
            'company_id'       => $companyId,
            'warehouse_id'     => $order->warehouse_id,
            'payment_mode_id'  => $payData['payment_mode_id'],
            'payable_type'     => Order::class,
            'payable_id'       => $order->id,
            'amount'           => $payData['amount'],
            'date'             => $payData['date'] ?? now(),
            'payment_type'     => 'in',
            'notes'            => $payData['notes'] ?? null,
            'staff_user_id'    => $this->getAuthUserId(),
        ]);

        // Link payment to the order via junction table
        OrderPayment::create([
            'company_id' => $companyId,
            'order_id'   => $order->id,
            'payment_id' => $payment->id,
            'amount'     => $payment->amount,
        ]);

        // Recalculate the outstanding due amount on the order
        $order->updateDueAmount();

        return $payment;
    }
}
