<?php
namespace App\Observers;
use App\Models\StockHistory;
class StockHistoryObserver {
    public function created(StockHistory $sh): void {
        \Log::info('Stock movement: '.$sh->type.' qty='.$sh->quantity.' product='.$sh->product_id.' wh='.$sh->warehouse_id);
    }
}
