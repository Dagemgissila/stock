<?php
namespace App\Models;

class Unit extends BaseModel
{
    protected $fillable = ['company_id','name','short_name','operator'];
    public function products() { return $this->hasMany(Product::class); }
}
