<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockHistoryController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = StockHistory::with(['product','warehouse'])
            ->orderBy('created_at', 'desc');
        if ($request->product_id)   $query->where('product_id',   $request->product_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->type)         $query->where('type',         $request->type);
        if ($request->from_date)    $query->whereDate('created_at', '>=', $request->from_date);
        if ($request->to_date)      $query->whereDate('created_at', '<=', $request->to_date);
        return $this->sendResponse($this->paginate($query));
    }
}
