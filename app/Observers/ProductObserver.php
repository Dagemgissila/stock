<?php
namespace App\Observers;

use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Warehouse;

class ProductObserver
{
    public function created(Product $product): void
    {
        $warehouses = Warehouse::where('company_id', $product->company_id)->get();
        foreach ($warehouses as $warehouse) {
            WarehouseStock::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['company_id' => $product->company_id, 'quantity'   => 0]
            );
        }
    }

    public function deleting(Product $product): void
    {
        WarehouseStock::where('product_id', $product->id)->delete();
    }
}
