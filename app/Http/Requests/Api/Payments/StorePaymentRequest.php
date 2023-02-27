<?php
namespace App\Http\Requests\Api\Payments;
use App\Http\Requests\Api\BaseRequest;

class StorePaymentRequest extends BaseRequest {
    public function rules(): array {
        return [
            'order_id'        => 'required|exists:orders,id',
            'amount'          => 'required|numeric|min:0.01',
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'date'            => 'required|date',
            'notes'           => 'nullable|string|max:500',
        ];
    }
}
