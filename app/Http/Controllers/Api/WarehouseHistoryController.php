<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\WarehouseHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseHistoryController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = WarehouseHistory::with(['fromWarehouse','toWarehouse','product']);
        if ($request->from_warehouse_id) $query->where('from_warehouse_id',$request->from_warehouse_id);
        if ($request->to_warehouse_id)   $query->where('to_warehouse_id',$request->to_warehouse_id);
        if ($request->product_id)        $query->where('product_id',$request->product_id);
        if ($request->from_date)         $query->whereDate('created_at','>=',$request->from_date);
        if ($request->to_date)           $query->whereDate('created_at','<=',$request->to_date);
        return $this->sendResponse($this->paginate($query->latest()));
    }
}
