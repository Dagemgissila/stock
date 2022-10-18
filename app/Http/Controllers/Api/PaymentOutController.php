<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Payment;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentOutController extends ApiBaseController {
    public function store(Request $request): JsonResponse {
        $request->validate(['order_id'=>'required|exists:orders,id',
            'amount'=>'required|numeric|min:0.01',
            'payment_mode_id'=>'required|exists:payment_modes,id',
            'date'=>'required|date']);
        $payment = Payment::create(array_merge($request->all(), [
            'company_id'   => auth('api')->user()->company_id,
            'payment_type' => 'out',
            'payable_type' => \App\Models\Order::class,
            'payable_id'   => $request->order_id,
        ]));
        OrderPayment::create(['company_id'=>$payment->company_id,
            'order_id'=>$request->order_id,'payment_id'=>$payment->id,'amount'=>$payment->amount]);
        return $this->sendResponse($payment->load('paymentMode'),'Payment sent',201);
    }
}
