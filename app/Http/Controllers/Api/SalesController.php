<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('sales')
            ->with(['items.product','party','warehouse'])
            ->withDateRange($request->from_date, $request->to_date);
        if ($request->order_status) $query->where('order_status', $request->order_status);
        if ($request->party_id)     $query->where('party_id',     $request->party_id);
        return $this->sendResponse($this->paginate($this->applySorting($query, 'order_date')));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'          => 'required|exists:warehouses,id',
            'order_date'            => 'required|date',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|numeric|min:0.001',
            'items.*.unit_price'    => 'required|numeric|min:0',
        ]);

        $companyId       = auth('api')->user()->company_id;
        $allowNegative   = Settings::getSetting('allow_negative_stock', $companyId);

        // Stock check (skipped if allow_negative_stock is enabled)
        if (!$allowNegative) {
            $shortages = [];
            foreach ($request->items as $item) {
                $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
                    ->where('product_id', $item['product_id'])->first();
                if (!$stock || $stock->quantity < $item['quantity']) {
                    $shortages[] = ['product_id' => $item['product_id'],
                                    'available'  => $stock->quantity ?? 0,
                                    'requested'  => $item['quantity']];
                }
            }
            if (!empty($shortages)) {
                return $this->sendError('Insufficient stock for one or more items.', $shortages);
            }
        }

        $order = Order::create(array_merge($request->except('items'), [
            'order_type'     => 'sales',
            'company_id'     => $companyId,
            'invoice_number' => 'SAL-' . strtoupper(uniqid()),
            'order_status'   => 'completed',
            'staff_user_id'  => auth('api')->id(),
        ]));

        $subtotal = 0;
        foreach ($request->items as $item) {
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $lineTotal;
            $order->items()->create(array_merge($item, ['company_id'=>$companyId,'subtotal'=>$lineTotal]));
        }

        $order->subtotal    = $subtotal;
        $order->grand_total = $order->calculateGrandTotal();
        $order->due_amount  = $order->grand_total;
        $order->save();

        return $this->sendResponse($order->load('items.product'), 'Sale created', 201);
    }

    public function show(int $id): JsonResponse
    {
        return $this->sendResponse(
            Order::with(['items.product','party','payments.payment.paymentMode','warehouse'])->findOrFail($id)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        Order::findOrFail($id)->delete();
        return $this->sendResponse([], 'Sale deleted');
    }
}
