<?php
namespace App\Observers;
use App\Models\Order;
use App\Models\WarehouseStock;
use App\Models\StockHistory;
use Illuminate\Support\Facades\{Log,Cache};

class OrderObserver
{
    public function saved(Order $order): void
    {
        // FIXED: guard with isDirty + completed status to prevent double deduction
        if ($order->isDirty('order_status') && $order->order_status === 'completed') {
            $this->syncStock($order);
        }
        Cache::forget('dashboard_' . $order->company_id);
    }

    public function deleted(Order $order): void
    {
        if ($order->order_status === 'completed') $this->reverseStock($order);
        Cache::forget('dashboard_' . $order->company_id);
    }

    private function syncStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id'=>$order->warehouse_id,'product_id'=>$item->product_id],
                ['company_id'=>$order->company_id,'quantity'=>0]);
            if ($order->order_type==='purchase') { $stock->increment('quantity',$item->quantity); $type='in'; }
            elseif ($order->order_type==='sales') { $stock->decrement('quantity',$item->quantity); $type='out'; }
            else continue;
            StockHistory::create(['company_id'=>$order->company_id,'warehouse_id'=>$order->warehouse_id,
                'product_id'=>$item->product_id,'quantity'=>$item->quantity,'order_type'=>$order->order_type,
                'order_id'=>$order->id,'type'=>$type,'created_by'=>$order->staff_user_id]);
        }
    }

    private function reverseStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock=WarehouseStock::where('warehouse_id',$order->warehouse_id)->where('product_id',$item->product_id)->first();
            if (!$stock) continue;
            if ($order->order_type==='sales')    $stock->increment('quantity',$item->quantity);
            if ($order->order_type==='purchase') $stock->decrement('quantity',$item->quantity);
        }
        Log::info('Stock reversed for deleted order: '.$order->invoice_number);
    }
}
