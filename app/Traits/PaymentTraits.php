<?php
namespace App\Traits;

trait PaymentTraits
{
    public function recalculateDue(\App\Models\Order $order): void
    {
        $paid = \App\Models\OrderPayment::where('order_id', $order->id)->sum('amount');
        $order->due_amount = max(0, $order->grand_total - $paid);
        $order->saveQuietly();
    }
}
