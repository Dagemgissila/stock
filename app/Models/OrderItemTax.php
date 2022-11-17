<?php
namespace App\Models;
class OrderItemTax extends BaseModel {
    protected $fillable = ['company_id','order_item_id','tax_id','tax_name','tax_rate','tax_amount'];
    public function orderItem() { return $this->belongsTo(OrderItem::class); }
    public function tax()       { return $this->belongsTo(Tax::class); }
}
