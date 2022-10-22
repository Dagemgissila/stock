<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VariationController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(Variation::all()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','type'=>'required|in:text,color,size']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Variation::create($data),'Variation created',201);
    }
    public function destroy(int $id): JsonResponse { Variation::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted'); }
}
