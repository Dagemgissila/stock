<?php
namespace App\Models;

class ProductDetails extends BaseModel
{
    protected $fillable = ['company_id','product_id','warehouse_id','quantity','stock_alert'];
    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
}
