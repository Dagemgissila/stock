<?php
namespace App\Observers;
use App\Models\Customer;
class CustomerObserver {
    public function saved(Customer $c): void { \Cache::forget('customers_'.$c->company_id); }
    public function deleted(Customer $c): void { \Cache::forget('customers_'.$c->company_id); }
}
