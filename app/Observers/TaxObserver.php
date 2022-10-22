<?php
namespace App\Observers;
use App\Models\Tax;
class TaxObserver {
    public function saved(Tax $t): void { \Cache::forget('taxes_'.$t->company_id); }
}
