<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\PaymentMode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentModeController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(PaymentMode::all()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required|max:100']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(PaymentMode::create($data),'Payment mode created',201);
    }
    public function destroy(int $id): JsonResponse {
        PaymentMode::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}
