<?php
namespace App\Observers;

use App\Models\OrderShippingAddress;

class OrderShippingAddressObserver
{
    public function creating(OrderShippingAddress $addr): void
    {
        if (empty($addr->company_id)) {
            $addr->company_id = auth()->user()->company_id ?? null;
        }
    }
}
