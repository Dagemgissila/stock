<?php
namespace App\Models;

class CustomField extends BaseModel
{
    protected $fillable = ['company_id','name','type','values','is_required','default_value'];
    protected $casts    = ['values' => 'array', 'is_required' => 'boolean'];

    public function productCustomFields() { return $this->hasMany(ProductCustomField::class); }
    public function orderCustomFields()   { return $this->hasMany(OrderCustomField::class); }
}
