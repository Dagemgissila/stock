<?php
namespace App\Models;
class Supplier extends BaseModel {
    use \App\Traits\PartyTraits;
    protected $fillable = ['company_id','name','email','phone','address','tax_number','status'];
    public function orders() { return $this->morphMany(Order::class,'party'); }
    public function getBalanceAttribute(): float { return $this->orders()->sum('due_amount'); }
}
