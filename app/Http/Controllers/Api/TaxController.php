<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(Tax::withCount('products')->get()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','rate'=>'required|numeric|min:0|max:100',
            'tax_type'=>'required|in:inclusive,exclusive']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Tax::create($data),'Tax created',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        Tax::findOrFail($id)->update($request->only(['name','rate','tax_type']));
        return $this->sendResponse(Tax::find($id),'Updated');
    }
    public function destroy(int $id): JsonResponse { Tax::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted'); }
}
