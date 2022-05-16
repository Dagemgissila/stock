<?php
namespace App\Models;

class ProductCustomField extends BaseModel
{
    protected $fillable = ['company_id','product_id','custom_field_id','value'];
    public function customField() { return $this->belongsTo(CustomField::class); }
    public function product()     { return $this->belongsTo(Product::class); }
}
