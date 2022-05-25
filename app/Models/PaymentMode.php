<?php
namespace App\Models;

class PaymentMode extends BaseModel
{
    protected $fillable = ['company_id','name','is_default'];
    public function payments() { return $this->hasMany(Payment::class); }
}
