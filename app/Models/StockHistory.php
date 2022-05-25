<?php
namespace App\Models;

class StockHistory extends BaseModel
{
    protected $fillable = [
        'company_id','warehouse_id','product_id','quantity',
        'order_type','order_id','type','created_by',
    ];

    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator()   { return $this->belongsTo(User::class,'created_by'); }
}
