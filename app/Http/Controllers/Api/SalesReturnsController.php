<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesReturnsController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('sales_return')->with(['items.product','party']);
        return $this->sendResponse($this->paginate($query->latest('order_date')));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'original_order_id'  => 'required|exists:orders,id',
            'warehouse_id'       => 'required|exists:warehouses,id',
            'order_date'         => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = auth('api')->user()->company_id;
        $original  = Order::findOrFail($request->original_order_id);

        $return = Order::create(array_merge($request->except('items','original_order_id'), [
            'order_type'     => 'sales_return',
            'company_id'     => $companyId,
            'invoice_number' => 'SR-' . strtoupper(uniqid()),
            'order_status'   => 'completed',
            'party_id'       => $original->party_id,
            'party_type'     => $original->party_type,
        ]));

        $subtotal = 0;
        foreach ($request->items as $item) {
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $lineTotal;
            $return->items()->create(array_merge($item, [
                'company_id' => $companyId,
                'subtotal'   => $lineTotal,
            ]));

            // Return stock to warehouse
            WarehouseStock::firstOrCreate(
                ['warehouse_id' => $request->warehouse_id, 'product_id' => $item['product_id']],
                ['company_id' => $companyId, 'quantity' => 0]
            )->increment('quantity', $item['quantity']);
        }

        $return->subtotal    = $subtotal;
        $return->grand_total = $return->calculateGrandTotal();
        $return->save();

        return $this->sendResponse($return->load('items.product'), 'Sales return created', 201);
    }
}
