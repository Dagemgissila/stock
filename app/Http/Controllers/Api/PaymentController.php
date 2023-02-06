<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Payment;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['paymentMode','payable']);
        if ($request->payment_type) $query->where('payment_type', $request->payment_type);
        if ($request->from_date)    $query->whereDate('date', '>=', $request->from_date);
        if ($request->to_date)      $query->whereDate('date', '<=', $request->to_date);
        return $this->sendResponse($this->paginate($query->latest()));
    }

    /** Stripe charge endpoint */
    public function stripeCharge(Request $request): JsonResponse
    {
        $request->validate([
            'order_id'         => 'required|exists:orders,id',
            'payment_method_id'=> 'required|string',
            'amount'           => 'required|numeric|min:0.01',
        ]);

        try {
            $stripe  = new \Stripe\StripeClient(config('cashier.secret'));
            $intent  = $stripe->paymentIntents->create([
                'amount'               => (int)($request->amount * 100),
                'currency'             => config('cashier.currency'),
                'payment_method'       => $request->payment_method_id,
                'confirmation_method'  => 'manual',
                'confirm'              => true,
                'return_url'           => config('cashier.payment_urls.success'),
            ]);

            if ($intent->status === 'succeeded') {
                $payment = Payment::create([
                    'company_id'       => auth('api')->user()->company_id,
                    'payable_type'     => \App\Models\Order::class,
                    'payable_id'       => $request->order_id,
                    'amount'           => $request->amount,
                    'date'             => now(),
                    'payment_type'     => 'in',
                    'notes'            => 'Stripe: '.$intent->id,
                ]);
                OrderPayment::create(['company_id'=>$payment->company_id,
                    'order_id'=>$request->order_id,'payment_id'=>$payment->id,'amount'=>$payment->amount]);
                return $this->sendResponse(['payment_intent_id'=>$intent->id],'Payment successful');
            }

            return $this->sendError('Payment requires additional action', ['client_secret'=>$intent->client_secret]);
        } catch (\Stripe\Exception\CardException $e) {
            return $this->sendError($e->getMessage(), [], 402);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        Payment::findOrFail($id)->delete();
        return $this->sendResponse([], 'Payment deleted');
    }
}
