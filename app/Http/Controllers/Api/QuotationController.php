<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuotationController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('quotation')
            ->with(['items.product','party'])
            ->withDateRange($request->from_date, $request->to_date);
        if ($request->order_status) $query->where('order_status', $request->order_status);
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
            'order_type'     => 'quotation',
            'company_id'     => auth('api')->user()->company_id,
            'invoice_number' => 'QUO-' . strtoupper(uniqid()),
            'order_status'   => 'draft',
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
        $order->due_amount  = 0; // quotations have no due amount
        $order->save();

        return $this->sendResponse($order->load('items.product'), 'Quotation created', 201);
    }

    public function convert(int $id): JsonResponse
    {
        $quotation = Order::ofType('quotation')->findOrFail($id);

        $sale = $quotation->replicate();
        $sale->order_type     = 'sales';
        $sale->invoice_number = 'SAL-' . strtoupper(uniqid());
        $sale->order_status   = 'completed';
        $sale->due_amount     = $sale->grand_total;
        $sale->save();

        foreach ($quotation->items as $item) {
            $newItem = $item->replicate();
            $newItem->order_id = $sale->id;
            $newItem->save();
        }

        $quotation->update(['order_status' => 'converted']);

        return $this->sendResponse($sale->load('items.product'), 'Quotation converted to sale');
    }

    public function destroy(int $id): JsonResponse
    {
        Order::findOrFail($id)->delete();
        return $this->sendResponse([], 'Quotation deleted');
    }
}
