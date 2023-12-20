<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseReturnsController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    { return $this->sendResponse($this->paginate(Order::ofType('purchase_return')->with(['items.product','party'])->latest('order_date'))); }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['original_order_id'=>'required|exists:orders,id','warehouse_id'=>'required|exists:warehouses,id',
            'order_date'=>'required|date','items'=>'required|array|min:1',
            'items.*.product_id'=>'required|exists:products,id','items.*.quantity'=>'required|numeric|min:0.001',
            'items.*.unit_price'=>'required|numeric|min:0']);
        $cid = auth('api')->user()->company_id;
        $orig = Order::findOrFail($request->original_order_id);
        $ret = Order::create(array_merge($request->except('items','original_order_id'),[
            'order_type'=>'purchase_return','company_id'=>$cid,
            'invoice_number'=>'PR-'.strtoupper(uniqid()),'order_status'=>'completed',
            'party_id'=>$orig->party_id,'party_type'=>$orig->party_type,'due_amount'=>0]));
        $sub=0;
        foreach ($request->items as $item) {
            $lt=$item['unit_price']*$item['quantity']; $sub+=$lt;
            $ret->items()->create(array_merge($item,['company_id'=>$cid,'subtotal'=>$lt]));
            WarehouseStock::where('warehouse_id',$request->warehouse_id)
                ->where('product_id',$item['product_id'])->decrement('quantity',$item['quantity']);
        }
        $ret->subtotal=$sub; $ret->grand_total=$ret->calculateGrandTotal(); $ret->save();
        return $this->sendResponse($ret->load('items.product'), 'Purchase return created', 201);
    }
}
