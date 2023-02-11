<?php
namespace App\Observers;
use App\Models\OrderPayment;
use App\Models\Order;
class OrderPaymentObserver {
    public function saved(OrderPayment $op): void { $this->sync($op); }
    public function deleted(OrderPayment $op): void { $this->sync($op); }
    private function sync(OrderPayment $op): void {
        $order = Order::find($op->order_id);
        if ($order) $order->updateDueAmount();
    }
}
