<?php
namespace App\Traits;

trait OrderTraits
{
    public function calculateGrandTotal(): float
    {
        return max(0, $this->subtotal - $this->discount + $this->tax_amount + $this->shipping);
    }

    public function updateDueAmount(): void
    {
        // Use OrderPayment sum to stay scoped to this order
        // to avoid counting payments attached to other orders
        $paid = $this->payments()->sum('amount');
        $this->due_amount = max(0, $this->grand_total - $paid);
        $this->saveQuietly();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeWithDateRange($query, ?string $from, ?string $to)
    {
        if ($from) $query->whereDate('order_date', '>=', $from);
        if ($to)   $query->whereDate('order_date', '<=', $to);
        return $query;
    }

    public function isPaid(): bool { return $this->due_amount <= 0; }
}
