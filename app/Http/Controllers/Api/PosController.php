<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PosController extends ApiBaseController
{
    public function products(Request $request): JsonResponse
    {
        $term = $request->input('q','');
        $wh   = $request->input('warehouse_id');
        $products = Product::with(['tax','unit','warehouseStocks'=>function($q)use($wh){if($wh)$q->where('warehouse_id',$wh);}])
            ->where('status',1)
            ->where(function($q)use($term){
                $q->where('name','like','%'.$term.'%')->orWhere('barcode',$term);
            })->limit(30)->get();
        return $this->sendResponse($products);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate(['warehouse_id'=>'required|exists:warehouses,id','items'=>'required|array|min:1',
            'items.*.product_id'=>'required|exists:products,id','items.*.quantity'=>'required|numeric|min:0.001',
            'items.*.unit_price'=>'required|numeric|min:0']);
        $cid = auth('api')->user()->company_id;
        $order = Order::create(['company_id'=>$cid,'warehouse_id'=>$request->warehouse_id,
            'order_type'=>'sales','invoice_number'=>'POS-'.strtoupper(uniqid()),
            'order_status'=>'completed','order_date'=>now()->toDateString(),
            'discount'=>$request->input('discount',0),'tax_amount'=>$request->input('tax_amount',0),
            'party_id'=>$request->customer_id,'party_type'=>$request->customer_id ? \App\Models\Customer::class : null,
            'staff_user_id'=>auth('api')->id()]);
        $sub=0;
        foreach ($request->items as $item){
            $lt=$item['unit_price']*$item['quantity']; $sub+=$lt;
            $order->items()->create(array_merge($item,['company_id'=>$cid,'subtotal'=>$lt]));
        }
        $order->subtotal=$sub; $order->grand_total=$order->calculateGrandTotal();
        $order->due_amount=$order->grand_total; $order->save();
        return $this->sendResponse($order->load('items.product'), 'POS order created', 201);
    }

    public function payment(Request $request, int $orderId): JsonResponse
    {
        $request->validate(['amount'=>'required|numeric|min:0.01','payment_mode_id'=>'required|exists:payment_modes,id']);
        $order = Order::findOrFail($orderId);
        $payment = Payment::create(['company_id'=>$order->company_id,'warehouse_id'=>$order->warehouse_id,
            'payment_mode_id'=>$request->payment_mode_id,'payable_type'=>Order::class,'payable_id'=>$order->id,
            'amount'=>$request->amount,'date'=>now(),'payment_type'=>'in','staff_user_id'=>auth('api')->id()]);
        OrderPayment::create(['company_id'=>$order->company_id,'order_id'=>$order->id,'payment_id'=>$payment->id,'amount'=>$payment->amount]);
        $order->updateDueAmount();
        return $this->sendResponse(['payment'=>$payment->load('paymentMode'),'order'=>$order->fresh(),
            'change'=>max(0,$request->amount-$order->fresh()->grand_total)],'Payment recorded');
    }
}
