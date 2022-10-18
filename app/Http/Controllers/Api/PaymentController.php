<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = Payment::with(['paymentMode','payable']);
        if ($request->payment_type) $query->where('payment_type',$request->payment_type);
        if ($request->from_date)    $query->whereDate('date','>=',$request->from_date);
        if ($request->to_date)      $query->whereDate('date','<=',$request->to_date);
        return $this->sendResponse($this->paginate($query->latest()));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['amount'=>'required|numeric|min:0.01',
            'date'=>'required|date','payment_mode_id'=>'required|exists:payment_modes,id',
            'payment_type'=>'required|in:in,out']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(Payment::create($data),'Payment recorded',201);
    }
    public function destroy(int $id): JsonResponse {
        Payment::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}
