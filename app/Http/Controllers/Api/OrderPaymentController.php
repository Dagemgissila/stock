<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\OrderPayment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderPaymentController extends ApiBaseController {
    public function index(int $orderId): JsonResponse {
        $payments = OrderPayment::with('payment.paymentMode')->where('order_id',$orderId)->get();
        return $this->sendResponse($payments);
    }
    public function destroy(int $id): JsonResponse {
        $op = OrderPayment::findOrFail($id);
        $order = Order::find($op->order_id);
        $op->delete();
        if ($order) $order->updateDueAmount();
        return $this->sendResponse([],'Payment record removed and due amount recalculated');
    }
}
