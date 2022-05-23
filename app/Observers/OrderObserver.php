<?php
namespace App\Observers;

use App\Models\Order;
use App\Models\WarehouseStock;
use App\Models\StockHistory;

class OrderObserver
{
    public function saved(Order $order): void
    {
        if ($order->isDirty('order_status')) {
            $this->syncStock($order);
        }
    }

    private function syncStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $order->warehouse_id, 'product_id' => $item->product_id],
                ['company_id'   => $order->company_id,  'quantity'   => 0]
            );

            $type = null;
            if ($order->order_type === 'purchase') {
                $stock->increment('quantity', $item->quantity);
                $type = 'in';
            } elseif ($order->order_type === 'sales') {
                $stock->decrement('quantity', $item->quantity);
                $type = 'out';
            }

            if ($type) {
                StockHistory::create([
                    'company_id'   => $order->company_id,
                    'warehouse_id' => $order->warehouse_id,
                    'product_id'   => $item->product_id,
                    'quantity'     => $item->quantity,
                    'order_type'   => $order->order_type,
                    'order_id'     => $order->id,
                    'type'         => $type,
                    'created_by'   => $order->staff_user_id,
                ]);
            }
        }
    }
}
