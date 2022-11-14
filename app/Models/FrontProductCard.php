<?php
namespace App\Models;
class FrontProductCard extends BaseModel {
    protected $fillable = ['company_id','product_id','title','description','image','is_featured','sort_order'];
    public function product() { return $this->belongsTo(Product::class); }
}
