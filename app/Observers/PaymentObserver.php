<?php
namespace App\Observers;

use App\Models\Payment;
use App\Models\Order;

class PaymentObserver
{
    public function saved(Payment $payment): void   { $this->updateOrderDue($payment); }
    public function deleted(Payment $payment): void { $this->updateOrderDue($payment); }

    private function updateOrderDue(Payment $payment): void
    {
        if ($payment->payable_type === Order::class && $payment->payable_id) {
            $order = Order::find($payment->payable_id);
            if ($order) $order->updateDueAmount();
        }
    }
}
