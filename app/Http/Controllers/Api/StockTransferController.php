<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\WarehouseStock;
use App\Models\WarehouseHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockTransferController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::ofType('stock_transfer')->with(['warehouse','fromWarehouse','items.product']);
        return $this->sendResponse($this->paginate($query->latest()));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'from_warehouse_id'  => 'required|exists:warehouses,id|different:warehouse_id',
            'warehouse_id'       => 'required|exists:warehouses,id',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
        ]);

        $companyId = auth('api')->user()->company_id;

        foreach ($request->items as $item) {
            $stock = WarehouseStock::where('warehouse_id', $request->from_warehouse_id)
                ->where('product_id', $item['product_id'])->first();
            if (!$stock || $stock->quantity < $item['quantity']) {
                return $this->sendError('Insufficient stock in source warehouse for product ID '.$item['product_id']);
            }
        }

        $order = Order::create([
            'company_id'        => $companyId,
            'warehouse_id'      => $request->warehouse_id,
            'from_warehouse_id' => $request->from_warehouse_id,
            'order_type'        => 'stock_transfer',
            'invoice_number'    => 'TRF-'.strtoupper(uniqid()),
            'order_status'      => 'completed',
            'order_date'        => now()->toDateString(),
        ]);

        foreach ($request->items as $item) {
            $order->items()->create(array_merge($item, ['company_id' => $companyId]));
            WarehouseStock::where('warehouse_id', $request->from_warehouse_id)
                ->where('product_id', $item['product_id'])->decrement('quantity', $item['quantity']);
            WarehouseStock::firstOrCreate(
                ['warehouse_id'=>$request->warehouse_id,'product_id'=>$item['product_id']],
                ['company_id'=>$companyId,'quantity'=>0])
                ->increment('quantity', $item['quantity']);
            WarehouseHistory::create([
                'company_id'        => $companyId,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->warehouse_id,
                'product_id'        => $item['product_id'],
                'quantity'          => $item['quantity'],
                'order_id'          => $order->id,
                'staff_user_id'     => auth('api')->id(),
            ]);
        }

        return $this->sendResponse($order->load('items.product'), 'Stock transferred', 201);
    }
}
