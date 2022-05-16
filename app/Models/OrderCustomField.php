<?php
namespace App\Models;

class OrderCustomField extends BaseModel
{
    protected $fillable = ['company_id','order_id','custom_field_id','value'];
    public function customField() { return $this->belongsTo(CustomField::class); }
    public function order()       { return $this->belongsTo(Order::class); }
}
