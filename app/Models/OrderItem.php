<?php
namespace App\Models;

class OrderItem extends BaseModel
{
    protected $fillable = [
        'company_id','order_id','product_id','unit_id',
        'unit_price','quantity','discount','tax_amount','subtotal','mrp',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function order()   { return $this->belongsTo(Order::class); }
    public function taxes()   { return $this->hasMany(OrderItemTax::class); }

    public function getLineTotalAttribute(): float
    {
        return ($this->unit_price * $this->quantity) - $this->discount + $this->tax_amount;
    }
}
