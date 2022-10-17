<?php
namespace App\Traits;
trait PartyTraits {
    public function scopeActive($query) { return $query->where('status',1); }
    public function getLedger(): array {
        $orders = $this->orders()->with('payments')->get();
        return [
            'total_orders'  => $orders->count(),
            'total_amount'  => $orders->sum('grand_total'),
            'paid_amount'   => $orders->flatMap->payments->sum('amount'),
            'due_amount'    => $orders->sum('due_amount'),
        ];
    }
}
