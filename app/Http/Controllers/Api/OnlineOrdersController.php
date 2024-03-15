<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\OrderShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OnlineOrdersController extends ApiBaseController {
    public function store(Request $request): JsonResponse {
        $request->validate(['warehouse_id'=>'required|exists:warehouses,id',
            'items'=>'required|array|min:1','items.*.product_id'=>'required|exists:products,id',
            'items.*.quantity'=>'required|numeric|min:1','shipping.address'=>'required|string']);
        $cid=$request->header('X-Company-Id');
        $order=Order::create(['company_id'=>$cid,'warehouse_id'=>$request->warehouse_id,'order_type'=>'sales',
            'invoice_number'=>'ONL-'.strtoupper(uniqid()),'order_status'=>'pending','order_date'=>now()->toDateString(),
            'party_id'=>auth('customer')->id(),'party_type'=>\App\Models\Customer::class]);
        $sub=0;
        foreach ($request->items as $item){
            $product=\App\Models\Product::findOrFail($item['product_id']);
            $lt=$product->sales_price*$item['quantity']; $sub+=$lt;
            $order->items()->create(['company_id'=>$cid,'product_id'=>$item['product_id'],'quantity'=>$item['quantity'],'unit_price'=>$product->sales_price,'subtotal'=>$lt]);
        }
        $order->subtotal=$sub; $order->grand_total=$sub+$request->input('shipping_fee',0);
        $order->due_amount=$order->grand_total; $order->save();
        if($request->shipping) OrderShippingAddress::create(array_merge($request->shipping,['company_id'=>$cid,'order_id'=>$order->id]));
        return $this->sendResponse($order->load('items.product'),'Order placed',201);
    }
    public function index(): JsonResponse {
        return $this->sendResponse(Order::ofType('sales')->where('party_id',auth('customer')->id())->with(['items.product','shippingAddress'])->latest()->paginate(10));
    }
}
