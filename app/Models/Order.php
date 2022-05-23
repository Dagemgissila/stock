<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends BaseModel
{
    use SoftDeletes, \App\Traits\OrderTraits;

    protected $fillable = [
        'company_id','warehouse_id','from_warehouse_id','order_type',
        'invoice_number','order_status','party_id','party_type',
        'subtotal','discount','tax_amount','shipping','grand_total',
        'due_amount','order_date','notes','staff_user_id',
    ];

    protected $casts = ['order_date' => 'date'];

    public function items()         { return $this->hasMany(OrderItem::class); }
    public function payments()      { return $this->hasMany(OrderPayment::class); }
    public function warehouse()     { return $this->belongsTo(Warehouse::class); }
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class,'from_warehouse_id'); }
    public function party()         { return $this->morphTo(); }
    public function customFields()  { return $this->hasMany(OrderCustomField::class); }
    public function shippingAddress(){ return $this->hasOne(OrderShippingAddress::class); }
}
