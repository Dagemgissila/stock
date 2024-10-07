<?php
namespace App\Models;
class StaffMember extends BaseModel{
    protected $fillable=['company_id','user_id','warehouse_id','designation'];
    public function user(){return $this->belongsTo(User::class);}
    public function warehouse(){return $this->belongsTo(Warehouse::class);}
}
