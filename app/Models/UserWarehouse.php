<?php
namespace App\Models;

class UserWarehouse extends BaseModel
{
    protected $fillable = ['company_id', 'user_id', 'warehouse_id'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function user()      { return $this->belongsTo(User::class); }
}
