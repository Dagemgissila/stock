<?php
namespace App\Models;
class UserAddress extends BaseModel {
    protected $fillable = ['company_id','customer_id','address','city','state','country','zip','is_default'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
