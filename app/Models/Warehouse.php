<?php
namespace App\Models;

class Warehouse extends BaseModel
{
    protected $fillable = [
        'company_id', 'name', 'slug', 'email', 'phone',
        'address', 'is_default', 'is_rtl', 'barcode_type',
    ];

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouses', 'warehouse_id', 'user_id');
    }

    public function history()
    {
        return $this->hasMany(WarehouseHistory::class, 'from_warehouse_id');
    }

    public function getStockValueAttribute(): float
    {
        return $this->stocks->sum(fn($s) => $s->quantity * ($s->product->purchase_price ?? 0));
    }
}
