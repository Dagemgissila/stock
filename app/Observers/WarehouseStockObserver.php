<?php
namespace App\Observers;
use App\Models\WarehouseStock;
class WarehouseStockObserver {
    public function saved(WarehouseStock $ws): void {
        \Cache::forget('stock_'.$ws->warehouse_id.'_'.$ws->product_id);
    }
}
