<?php
namespace App\Models;

class Tax extends BaseModel
{
    protected $fillable = ['company_id','name','rate','tax_type'];
    public function products() { return $this->hasMany(Product::class); }
}
