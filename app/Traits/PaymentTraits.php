<?php
namespace App\Traits;

use App\Models\Order;
use App\Models\OrderPayment;

trait PaymentTraits
{
    /**
     * Recalculate order due_amount based on all linked OrderPayment records.
     * Uses OrderPayment (not Payment directly) to stay order-scoped.
     */
    public function recalculateDue(Order $order): void
    {
        $paid = OrderPayment::where('order_id', $order->id)->sum('amount');
        $order->due_amount = max(0, $order->grand_total - $paid);
        $order->saveQuietly();
    }

    /**
     * Check if a payment amount would exceed the order's remaining due.
     */
    public function wouldOverpay(Order $order, float $amount): bool
    {
        return $amount > $order->due_amount;
    }

    /**
     * Format a payment history entry for API response.
     */
    public function formatPaymentHistory(Order $order): array
    {
        return $order->payments()->with('payment.paymentMode')->get()->map(fn($op) => [
            'amount'       => $op->amount,
            'date'         => optional($op->payment)->date?->format('Y-m-d'),
            'mode'         => optional(optional($op->payment)->paymentMode)->name,
            'payment_id'   => $op->payment_id,
        ])->toArray();
    }
}
