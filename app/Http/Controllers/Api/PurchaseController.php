<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('purchase')
            ->with(['items.product','party','warehouse'])
            ->withDateRange($request->from_date, $request->to_date);
        if ($request->party_id) $query->where('party_id', $request->party_id);
        return $this->sendResponse($this->paginate($query->latest('order_date')));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'       => 'required|exists:warehouses,id',
            'order_date'         => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order = Order::create(array_merge($request->except('items'), [
            'order_type'     => 'purchase',
            'company_id'     => auth('api')->user()->company_id,
            'invoice_number' => 'PUR-' . strtoupper(uniqid()),
            'order_status'   => $request->input('order_status','ordered'),
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

        return $this->sendResponse($order->load('items.product'), 'Purchase order created', 201);
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $order = Order::with('items')->findOrFail($id);

        foreach ($order->items as $item) {
            WarehouseStock::firstOrCreate(
                ['warehouse_id' => $order->warehouse_id, 'product_id' => $item->product_id],
                ['company_id'   => $order->company_id,   'quantity'   => 0]
            )->increment('quantity', $item->quantity);
        }

        $order->update(['order_status' => 'received']);
        return $this->sendResponse($order->fresh(), 'Purchase marked as received, stock updated');
    }

    public function show(int $id): JsonResponse
    {
        return $this->sendResponse(
            Order::with(['items.product','party','payments','warehouse'])->findOrFail($id)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        Order::findOrFail($id)->delete();
        return $this->sendResponse([], 'Purchase order deleted');
    }
}
