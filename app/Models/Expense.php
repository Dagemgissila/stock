<?php
namespace App\Models;

class Expense extends BaseModel
{
    protected $fillable=['company_id','expense_category_id','warehouse_id','user_id','amount','date','note','attachment','is_recurring'];
    protected $casts=['date'=>'datetime','is_recurring'=>'boolean'];

    public function category()  { return $this->belongsTo(ExpenseCategory::class,'expense_category_id'); }
    public function user()      { return $this->belongsTo(User::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    public function scopeWithDateRange($query, ?string $from, ?string $to)
    {
        if ($from) $query->whereDate('date', '>=', $from);
        if ($to)   $query->whereDate('date', '<=', $to);
        return $query;
    }
}
