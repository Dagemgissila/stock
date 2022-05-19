<?php
namespace App\Models;

class ExpenseCategory extends BaseModel
{
    protected $fillable = ['company_id','name','description'];
    public function expenses() { return $this->hasMany(Expense::class); }
}
