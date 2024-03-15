<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\FrontProductCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FrontProductCardsController extends ApiBaseController {
    public function index(): JsonResponse {
        return $this->sendResponse(FrontProductCard::with('product')->orderBy('sort_order')->orderByDesc('id')->get());
    }
    public function store(Request $request): JsonResponse {
        $data=$request->validate(['product_id'=>'required|exists:products,id','title'=>'nullable','sort_order'=>'nullable|integer']);
        $data['company_id']=auth('api')->user()->company_id;
        return $this->sendResponse(FrontProductCard::create($data),'Card created',201);
    }
    public function destroy(int $id): JsonResponse {
        FrontProductCard::findOrFail($id)->delete();
        return $this->sendResponse([],'Deleted');
    }
}
