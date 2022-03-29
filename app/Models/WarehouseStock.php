<?php
namespace App\Models;

class WarehouseStock extends BaseModel
{
    protected $fillable = ['company_id', 'warehouse_id', 'product_id', 'quantity'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product()   { return $this->belongsTo(Product::class); }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=',
            $this->getTable() . '.product.stock_alert');
    }
}
