<?php
namespace App\Observers;

use App\Models\UserAddress;

class UserAddressObserver
{
    public function saving(UserAddress $address): void
    {
        // Ensure only one default address per customer
        if ($address->is_default) {
            UserAddress::where('customer_id', $address->customer_id)
                ->where('id', '!=', $address->id ?? 0)
                ->update(['is_default' => false]);
        }
    }
}
