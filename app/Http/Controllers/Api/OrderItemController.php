<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderItemController extends ApiBaseController {
    public function store(Request $request): JsonResponse {
        $request->validate(['order_id'=>'required|exists:orders,id',
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|numeric|min:0.001','unit_price'=>'required|numeric|min:0']);
        $order = Order::findOrFail($request->order_id);
        $item  = $order->items()->create(array_merge($request->all(), [
            'company_id'=>$order->company_id,
            'subtotal'=>$request->unit_price * $request->quantity,
        ]));
        $order->subtotal = $order->items()->sum('subtotal');
        $order->grand_total = $order->calculateGrandTotal();
        $order->saveQuietly();
        return $this->sendResponse($item->load('product'),'Item added',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        $item = OrderItem::findOrFail($id);
        $item->update($request->only(['quantity','unit_price','discount']));
        $item->subtotal = $item->unit_price * $item->quantity - $item->discount;
        $item->saveQuietly();
        $order = $item->order;
        $order->subtotal    = $order->items()->sum('subtotal');
        $order->grand_total = $order->calculateGrandTotal();
        $order->saveQuietly();
        return $this->sendResponse($item->fresh()->load('product'),'Item updated');
    }
    public function destroy(int $id): JsonResponse {
        $item  = OrderItem::findOrFail($id);
        $order = $item->order;
        $item->delete();
        $order->subtotal    = $order->items()->sum('subtotal');
        $order->grand_total = $order->calculateGrandTotal();
        $order->saveQuietly();
        return $this->sendResponse([],'Item removed');
    }
}
