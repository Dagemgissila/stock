<?php
namespace App\Models;

class Payment extends BaseModel
{
    use \App\Traits\PaymentTraits;
    protected $fillable=['company_id','warehouse_id','payment_mode_id','staff_user_id','payable_type','payable_id','amount','date','payment_type','notes'];
    protected $casts=['date'=>'datetime'];

    public function paymentMode() { return $this->belongsTo(PaymentMode::class); }
    public function payable()     { return $this->morphTo(); }
    public function warehouse()   { return $this->belongsTo(Warehouse::class); }

    public function scopeWithDateRange($query, ?string $from, ?string $to)
    {
        if ($from) $query->whereDate('date', '>=', $from);
        if ($to)   $query->whereDate('date', '<=', $to);
        return $query;
    }
}
