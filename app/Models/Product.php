<?php
namespace App\Models;

class Product extends BaseModel
{
    protected $fillable = [
        'company_id','category_id','brand_id','unit_id','tax_id',
        'name','slug','barcode','image','purchase_price',
        'sales_price','mrp','stock_alert','status',
    ];

    public function category()       { return $this->belongsTo(Category::class); }
    public function brand()          { return $this->belongsTo(Brand::class); }
    public function unit()           { return $this->belongsTo(Unit::class); }
    public function tax()            { return $this->belongsTo(Tax::class); }
    public function warehouseStocks(){ return $this->hasMany(WarehouseStock::class); }
    public function variants()       { return $this->hasMany(ProductVariant::class); }
    public function customFields()   { return $this->hasMany(ProductCustomField::class); }

    public function getTotalStockAttribute(): float
    {
        return $this->warehouseStocks->sum('quantity');
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->total_stock <= 0)            return 'out_of_stock';
        if ($this->total_stock <= $this->stock_alert) return 'low_stock';
        return 'in_stock';
    }
}
