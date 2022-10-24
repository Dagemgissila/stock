<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\StockAdjustment;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['product','warehouse']);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->from_date)    $query->whereDate('created_at', '>=', $request->from_date);
        if ($request->to_date)      $query->whereDate('created_at', '<=', $request->to_date);
        return $this->sendResponse($this->paginate($query->latest()));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'quantity'     => 'required|numeric|min:0.001',
            'type'         => 'required|in:addition,subtraction,damage,expiry',
            'reason'       => 'nullable|string|max:500',
        ]);

        $data = array_merge($request->all(), [
            'company_id'  => auth('api')->user()->company_id,
            'adjusted_by' => auth('api')->id(),
        ]);

        $adjustment = StockAdjustment::create($data);
        return $this->sendResponse(
            $adjustment->load(['product','warehouse']),
            'Stock adjusted successfully', 201
        );
    }

    public function destroy(int $id): JsonResponse
    {
        StockAdjustment::findOrFail($id)->delete();
        return $this->sendResponse([], 'Adjustment deleted');
    }
}
