<?php
namespace App\Models;

class Brand extends BaseModel
{
    protected $fillable = ['company_id','name','slug','image'];
    public function products() { return $this->hasMany(Product::class); }
}
