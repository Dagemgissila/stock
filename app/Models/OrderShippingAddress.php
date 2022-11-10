<?php
namespace App\Models;

class OrderShippingAddress extends BaseModel
{
    protected $fillable = [
        'company_id','order_id','first_name','last_name',
        'email','phone','address','city','state','country','zip',
    ];

    public function order() { return $this->belongsTo(Order::class); }

    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address, $this->city, $this->state, $this->country, $this->zip,
        ]));
    }
}
