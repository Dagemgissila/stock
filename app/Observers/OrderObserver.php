<?php
namespace App\Observers;

use App\Models\Order;
use App\Models\WarehouseStock;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function saved(Order $order): void
    {
        // FIXED: only sync stock when order_status actually changed
        // Previously this fired on every save, causing double-deductions
        if ($order->isDirty('order_status') && $order->order_status === 'completed') {
            $this->syncStock($order);
        }
    }

    public function deleted(Order $order): void
    {
        // Reverse stock on soft-delete of completed orders
        if ($order->order_status === 'completed') {
            $this->reverseStock($order);
        }
    }

    private function syncStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $order->warehouse_id, 'product_id' => $item->product_id],
                ['company_id' => $order->company_id, 'quantity' => 0]
            );

            $type = null;
            if ($order->order_type === 'purchase') {
                $stock->increment('quantity', $item->quantity); $type = 'in';
            } elseif ($order->order_type === 'sales') {
                $stock->decrement('quantity', $item->quantity); $type = 'out';
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

    private function reverseStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = WarehouseStock::where('warehouse_id', $order->warehouse_id)
                ->where('product_id', $item->product_id)->first();
            if (!$stock) continue;
            if ($order->order_type === 'sales')    $stock->increment('quantity', $item->quantity);
            if ($order->order_type === 'purchase') $stock->decrement('quantity', $item->quantity);
        }
        Log::info('Stock reversed for deleted order: ' . $order->invoice_number);
    }
}
