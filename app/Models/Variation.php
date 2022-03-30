<?php
namespace App\Models;

class Variation extends BaseModel
{
    protected $fillable = ['company_id','name','type'];
    public function productVariants() { return $this->hasMany(ProductVariant::class); }
}
