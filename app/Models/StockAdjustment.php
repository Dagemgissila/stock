<?php
namespace App\Models;

class StockAdjustment extends BaseModel
{
    protected $fillable = [
        'company_id','warehouse_id','product_id',
        'quantity','type','reason','adjusted_by',
    ];

    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function adjustedBy(){ return $this->belongsTo(User::class,'adjusted_by'); }
}
