<?php
namespace App\Observers;

use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\WarehouseStock;

class StockAdjustmentObserver
{
    public function created(StockAdjustment $adj): void
    {
        // Write audit record
        StockHistory::create([
            'company_id'   => $adj->company_id,
            'warehouse_id' => $adj->warehouse_id,
            'product_id'   => $adj->product_id,
            'quantity'     => $adj->quantity,
            'order_type'   => 'adjustment',
            'type'         => $adj->type === 'addition' ? 'in' : 'out',
            'created_by'   => $adj->adjusted_by,
        ]);

        // Update actual stock
        $stock = WarehouseStock::firstOrCreate(
            ['warehouse_id' => $adj->warehouse_id, 'product_id' => $adj->product_id],
            ['company_id'   => $adj->company_id,   'quantity'   => 0]
        );

        if (in_array($adj->type, ['addition'])) {
            $stock->increment('quantity', $adj->quantity);
        } else {
            $stock->decrement('quantity', $adj->quantity);
        }
    }
}
