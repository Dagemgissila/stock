<?php
namespace App\Models;

class Category extends BaseModel
{
    protected $fillable = ['company_id','parent_id','name','slug','image'];
    public function products()  { return $this->hasMany(Product::class); }
    public function parent()    { return $this->belongsTo(Category::class,'parent_id'); }
    public function children()  { return $this->hasMany(Category::class,'parent_id'); }
}
