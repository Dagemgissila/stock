<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class PurchaseController
 *
 * Manages all purchase order operations in the Stock Management System.
 * Purchase orders track inventory acquisition from suppliers and drive
 * stock replenishment workflows.
 *
 * The purchase lifecycle:
 *   1. Create: Order is created with status 'ordered'
 *   2. Receive: Items are received, stock levels are increased
 *   3. Pay: Payments recorded, due_amount reduced
 *   4. Complete: Order marked complete when fully paid and received
 *
 * Key Responsibilities:
 *   - List and filter purchase orders with pagination
 *   - Create purchase orders linked to suppliers
 *   - Receive stock into the warehouse when goods arrive
 *   - Record payments to suppliers (outgoing payments)
 *   - Cancel orders and reverse stock if already received
 *   - Provide purchase summaries for reporting
 *
 * @package App\Http\Controllers\Api
 * @author  StockManager Development Team
 * @version 3.0.0
 */
class PurchaseController extends ApiBaseController
{
    // ─── Constants ────────────────────────────────────────────────────────────

    /** @var string Invoice prefix for purchase orders */
    private const INVOICE_PREFIX = 'PUR';

    /** @var array<string> Statuses allowed for bulk status updates */
    private const BULK_STATUSES = ['ordered', 'received', 'cancelled', 'pending'];

    // ─── Index ────────────────────────────────────────────────────────────────

    /**
     * Display a paginated list of purchase orders.
     *
     * Applies optional filters for date range, party (supplier),
     * warehouse, order status, and full-text search on invoice number.
     * Eager-loads items with their products, the supplier party, and
     * the warehouse for efficient JSON rendering.
     *
     * @param  Request  $request  Incoming HTTP request with filters
     * @return JsonResponse       Paginated list of purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('purchase')
            ->with([
                'items.product',
                'party',
                'warehouse',
                'staffMember:id,name',
            ])
            ->withDateRange($request->from_date, $request->to_date);

        // Filter by supplier
        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id)
                  ->where('party_type', Supplier::class);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by order status
        if ($request->filled('order_status')) {
            $query->where('order_status', $request->order_status);
        }

        // Search by invoice number or supplier name
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                  ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        return $this->sendResponse(
            $this->paginate($this->applySorting($query, 'order_date', 'desc')),
            'Purchase orders retrieved successfully'
        );
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    /**
     * Create a new purchase order.
     *
     * Creates the order header and all line items. The order is set to
     * 'ordered' status; stock is NOT incremented at this point. Stock
     * is only added when the receive() endpoint is called.
     *
     * @param  Request  $request  Purchase order data
     * @return JsonResponse       201 with new order data
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id'           => 'required|integer|exists:warehouses,id',
            'supplier_id'            => 'nullable|integer|exists:suppliers,id',
            'order_date'             => 'required|date',
            'discount'               => 'nullable|numeric|min:0',
            'tax_amount'             => 'nullable|numeric|min:0',
            'shipping'               => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string|max:2000',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.001',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0',
            'items.*.tax_id'         => 'nullable|integer|exists:taxes,id',
        ]);

        $companyId = $this->getCompanyId();

        try {
            $order = DB::transaction(function () use ($validated, $request, $companyId) {
                // Create the purchase order header
                $order = Order::create([
                    'company_id'     => $companyId,
                    'warehouse_id'   => $validated['warehouse_id'],
                    'order_type'     => Order::TYPE_PURCHASE,
                    'invoice_number' => $this->generateInvoiceNumber(self::INVOICE_PREFIX),
                    'order_status'   => Order::STATUS_ORDERED,
                    'order_date'     => $validated['order_date'],
                    'party_id'       => $validated['supplier_id'] ?? null,
                    'party_type'     => isset($validated['supplier_id']) ? Supplier::class : null,
                    'discount'       => $validated['discount'] ?? 0.00,
                    'tax_amount'     => $validated['tax_amount'] ?? 0.00,
                    'shipping'       => $validated['shipping'] ?? 0.00,
                    'notes'          => $validated['notes'] ?? null,
                    'staff_user_id'  => $this->getAuthUserId(),
                    'subtotal'       => 0.00,
                    'grand_total'    => 0.00,
                    'due_amount'     => 0.00,
                ]);

                // Create all line items and calculate subtotal
                $subtotal = 0.00;
                foreach ($request->items as $item) {
                    $lineTotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $lineDisc  = (float) ($item['discount'] ?? 0);
                    $lineFinal = max(0, $lineTotal - $lineDisc);
                    $subtotal += $lineFinal;

                    $order->items()->create([
                        'company_id'  => $companyId,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['unit_price'],
                        'discount'    => $lineDisc,
                        'subtotal'    => $lineFinal,
                        'tax_id'      => $item['tax_id'] ?? null,
                    ]);
                }

                // Persist totals
                $order->subtotal    = $subtotal;
                $order->grand_total = $order->calculateGrandTotal();
                $order->due_amount  = $order->grand_total;
                $order->save();

                return $order;
            });

            $this->logAction('purchase.created', [
                'order_id' => $order->id,
                'total'    => $order->grand_total,
            ]);

            return $this->sendResponse(
                $order->load(['items.product', 'party', 'warehouse']),
                'Purchase order created successfully',
                201
            );
        } catch (\Throwable $e) {
            Log::error('PurchaseController@store failed', ['error' => $e->getMessage()]);
            return $this->sendError('Failed to create purchase order.', [], 500);
        }
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    /**
     * Retrieve a single purchase order with all related data.
     *
     * @param  int  $id  Purchase order ID
     * @return JsonResponse  Full order detail
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
        ])->ofType('purchase')
          ->findOrFail($id);

        $data                   = $order->toArray();
        $data['total_paid']     = $order->total_paid;
        $data['is_fully_paid']  = $order->is_fully_paid;
        $data['payment_progress'] = $order->payment_progress;

        return $this->sendResponse($data, 'Purchase order retrieved successfully');
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * Update header fields of a purchase order.
     *
     * Only 'ordered' or 'pending' orders can be edited.
     * Already-received or completed orders are locked.
     *
     * @param  Request  $request  Fields to update
     * @param  int      $id       Order ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::ofType('purchase')->findOrFail($id);

        if (!$order->isEditable()) {
            return $this->sendError(
                "Cannot modify a purchase order with status '{$order->order_status}'.",
                [],
                422
            );
        }

        $validated = $request->validate([
            'order_date'   => 'sometimes|date',
            'supplier_id'  => 'nullable|integer|exists:suppliers,id',
            'discount'     => 'nullable|numeric|min:0',
            'tax_amount'   => 'nullable|numeric|min:0',
            'shipping'     => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:2000',
        ]);

        $order->update(array_filter($validated, fn($v) => $v !== null));

        if (isset($validated['supplier_id'])) {
            $order->update([
                'party_id'   => $validated['supplier_id'],
                'party_type' => Supplier::class,
            ]);
        }

        $order->recalculateTotals();

        return $this->sendResponse(
            $order->fresh()->load(['items.product', 'party', 'warehouse']),
            'Purchase order updated successfully'
        );
    }

    // ─── Receive ──────────────────────────────────────────────────────────────

    /**
     * Mark a purchase order as received and add stock to the warehouse.
     *
     * Iterates over all line items, increments the corresponding
     * WarehouseStock records, and records a StockHistory entry for each.
     * The order status is updated to 'received'.
     *
     * Supports partial receiving via an optional 'items' array.
     * If items are provided, only those quantities are received;
     * otherwise all line item quantities are received in full.
     *
     * @param  Request  $request  Optional: items array for partial receive
     * @param  int      $id       Purchase order ID
     * @return JsonResponse       Updated order with received status
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $order = Order::with('items')->ofType('purchase')->findOrFail($id);

        if ($order->order_status === Order::STATUS_RECEIVED) {
            return $this->sendError('This purchase order has already been received.', [], 422);
        }

        if ($order->order_status === Order::STATUS_CANCELLED) {
            return $this->sendError('Cannot receive a cancelled purchase order.', [], 422);
        }

        // Optional partial receive: map product_id => qty_to_receive
        $receiveQuantities = [];
        if ($request->filled('items')) {
            $request->validate([
                'items'              => 'array',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity'   => 'required|numeric|min:0.001',
            ]);
            foreach ($request->items as $item) {
                $receiveQuantities[(int) $item['product_id']] = (float) $item['quantity'];
            }
        }

        $companyId = $this->getCompanyId();

        DB::transaction(function () use ($order, $receiveQuantities, $companyId) {
            foreach ($order->items as $item) {
                // Determine quantity to receive for this item
                $qtyToReceive = isset($receiveQuantities[$item->product_id])
                    ? min($receiveQuantities[$item->product_id], (float) $item->quantity)
                    : (float) $item->quantity;

                if ($qtyToReceive <= 0) {
                    continue; // Skip items with zero receive quantity
                }

                // Increment warehouse stock (create record if not exists)
                WarehouseStock::firstOrCreate(
                    [
                        'warehouse_id' => $order->warehouse_id,
                        'product_id'   => $item->product_id,
                    ],
                    [
                        'company_id' => $companyId,
                        'quantity'   => 0,
                    ]
                )->increment('quantity', $qtyToReceive);

                // Record stock history entry
                StockHistory::create([
                    'company_id'   => $companyId,
                    'warehouse_id' => $order->warehouse_id,
                    'product_id'   => $item->product_id,
                    'quantity'     => $qtyToReceive,
                    'order_type'   => Order::TYPE_PURCHASE,
                    'order_id'     => $order->id,
                    'type'         => 'in',
                    'created_by'   => $this->getAuthUserId(),
                ]);
            }

            $order->update(['order_status' => Order::STATUS_RECEIVED]);
        });

        $this->logAction('purchase.received', ['order_id' => $id]);

        return $this->sendResponse(
            $order->fresh()->load(['items.product', 'warehouse']),
            'Stock received and warehouse updated successfully'
        );
    }

    // ─── Payment ──────────────────────────────────────────────────────────────

    /**
     * Record an outgoing payment for a purchase order.
     *
     * Creates a Payment of type 'out' (payment to supplier),
     * links it via OrderPayment, and recalculates due_amount.
     *
     * @param  Request  $request  Payment details
     * @param  int      $id       Purchase order ID
     * @return JsonResponse       Updated payment summary
     */
    public function addPayment(Request $request, int $id): JsonResponse
    {
        $order = Order::ofType('purchase')->findOrFail($id);

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_mode_id'  => 'required|integer|exists:payment_modes,id',
            'date'             => 'nullable|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        if ((float) $validated['amount'] > $order->due_amount) {
            return $this->sendError(
                "Payment amount exceeds outstanding balance of {$order->due_amount}.",
                [],
                422
            );
        }

        try {
            $payment = DB::transaction(function () use ($order, $validated) {
                $companyId = $this->getCompanyId();

                $payment = Payment::create([
                    'company_id'      => $companyId,
                    'warehouse_id'    => $order->warehouse_id,
                    'payment_mode_id' => $validated['payment_mode_id'],
                    'payable_type'    => Order::class,
                    'payable_id'      => $order->id,
                    'amount'          => $validated['amount'],
                    'date'            => $validated['date'] ?? now(),
                    'payment_type'    => 'out',
                    'notes'           => $validated['notes'] ?? null,
                    'staff_user_id'   => $this->getAuthUserId(),
                ]);

                OrderPayment::create([
                    'company_id' => $companyId,
                    'order_id'   => $order->id,
                    'payment_id' => $payment->id,
                    'amount'     => $payment->amount,
                ]);

                $order->updateDueAmount();

                return $payment;
            });

            return $this->sendResponse([
                'payment'       => $payment->load('paymentMode'),
                'order'         => $order->fresh(),
                'remaining_due' => $order->fresh()->due_amount,
            ], 'Payment recorded successfully');
        } catch (\Throwable $e) {
            return $this->sendError('Failed to record payment.', [], 500);
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * Delete a purchase order (soft delete).
     *
     * Received orders cannot be deleted as they have impacted inventory.
     * Orders with payment records are also protected.
     *
     * @param  int  $id  Purchase order ID
     * @return JsonResponse  Confirmation message
     */
    public function destroy(int $id): JsonResponse
    {
        $order = Order::ofType('purchase')->findOrFail($id);

        if ($order->order_status === Order::STATUS_RECEIVED) {
            return $this->sendError(
                'Cannot delete a received purchase order. The stock has already been added.',
                [],
                422
            );
        }

        if ($order->payments()->exists()) {
            return $this->sendError(
                'Cannot delete a purchase order with payment records.',
                [],
                422
            );
        }

        $this->logAction('purchase.deleted', ['order_id' => $id]);
        $order->delete();

        return $this->sendResponse([], 'Purchase order deleted successfully');
    }

    // ─── Summary ──────────────────────────────────────────────────────────────

    /**
     * Get purchase order aggregate summary for reporting.
     *
     * Returns totals grouped overall and by order status.
     *
     * @param  Request  $request  Optional date range filters
     * @return JsonResponse       Purchase summary stats
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Order::ofType('purchase')
            ->withDateRange($request->from_date, $request->to_date);

        $overall = (clone $query)->selectRaw(
            'COUNT(*) as total_orders,
             COALESCE(SUM(grand_total), 0) as total_purchase_value,
             COALESCE(SUM(grand_total - due_amount), 0) as total_paid,
             COALESCE(SUM(due_amount), 0) as total_outstanding'
        )->first();

        $byStatus = (clone $query)->selectRaw(
            'order_status, COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total'
        )->groupBy('order_status')->get();

        return $this->sendResponse(
            ['overall' => $overall, 'by_status' => $byStatus],
            'Purchase summary retrieved'
        );
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Generate a unique purchase invoice number.
     *
     * @param  string  $prefix
     * @return string
     */
    private function generateInvoiceNumber(string $prefix): string
    {
        return $prefix . '-' . strtoupper(Str::random(8)) . '-' . date('Ymd');
    }
}
