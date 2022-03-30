<?php
namespace App\Models;

class ProductVariant extends BaseModel
{
    protected $fillable = ['company_id','product_id','variation_id','name','price','quantity'];
    public function product()   { return $this->belongsTo(Product::class); }
    public function variation() { return $this->belongsTo(Variation::class); }
}
