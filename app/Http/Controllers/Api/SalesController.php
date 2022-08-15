<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
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
        return $this->sendResponse($this->paginate($query->latest('order_date')));
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

        // Stock availability check
        if (!$this->checkStock($request->warehouse_id, $request->items)) {
            return $this->sendError('Insufficient stock for one or more items.', [], 422);
        }

        $order = Order::create(array_merge($request->except('items'), [
            'order_type'     => 'sales',
            'company_id'     => auth('api')->user()->company_id,
            'invoice_number' => 'SAL-' . strtoupper(uniqid()),
            'order_status'   => $request->input('order_status','completed'),
        ]));

        $subtotal = 0;
        foreach ($request->items as $item) {
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $lineTotal;
            $order->items()->create(array_merge($item, [
                'company_id' => $order->company_id,
                'subtotal'   => $lineTotal,
            ]));
        }

        $order->subtotal    = $subtotal;
        $order->grand_total = $order->calculateGrandTotal();
        $order->due_amount  = $order->grand_total;
        $order->save();

        return $this->sendResponse($order->load('items.product'), 'Sale order created', 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.product','party','payments.paymentMode','warehouse'])->findOrFail($id);
        return $this->sendResponse($order);
    }

    public function destroy(int $id): JsonResponse
    {
        Order::findOrFail($id)->delete();
        return $this->sendResponse([], 'Sale order deleted');
    }

    private function checkStock(int $warehouseId, array $items): bool
    {
        foreach ($items as $item) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $item['product_id'])->first();
            if (!$stock || $stock->quantity < $item['quantity']) return false;
        }
        return true;
    }
}
