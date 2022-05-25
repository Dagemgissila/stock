<?php
namespace App\Models;

class OrderPayment extends BaseModel
{
    protected $fillable = ['company_id','order_id','payment_id','amount'];

    public function order()   { return $this->belongsTo(Order::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
}
