<?php
namespace App\Observers;

use App\Models\Warehouse;
use Illuminate\Support\Str;

class WarehouseObserver
{
    public function creating(Warehouse $warehouse): void
    {
        if (empty($warehouse->slug)) {
            $warehouse->slug = Str::slug($warehouse->name) . '-' . time();
        }
    }

    public function saved(Warehouse $warehouse): void
    {
        \Cache::forget('warehouses_' . $warehouse->company_id);
    }
}
