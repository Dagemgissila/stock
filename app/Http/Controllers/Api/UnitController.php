<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(Unit::withCount('products')->get()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','short_name'=>'required|max:10']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Unit::create($data),'Unit created',201);
    }
    public function destroy(int $id): JsonResponse { Unit::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted'); }
}
