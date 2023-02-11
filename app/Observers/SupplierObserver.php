<?php
namespace App\Observers;
use App\Models\Supplier;
class SupplierObserver {
    public function saved(Supplier $s): void { \Cache::forget('suppliers_'.$s->company_id); }
}
