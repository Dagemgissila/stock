<?php
namespace App\Observers;
use App\Models\Unit;
class UnitObserver {
    public function saved(Unit $u): void { \Cache::forget('units_'.$u->company_id); }
}
