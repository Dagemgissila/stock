<?php
namespace App\Observers;
use App\Models\WarehouseHistory;
class WarehouseHistoryObserver {
    public function created(WarehouseHistory $wh): void {
        \Log::info('Stock transferred from '.$wh->from_warehouse_id.' to '.$wh->to_warehouse_id.': qty '.$wh->quantity);
    }
}
