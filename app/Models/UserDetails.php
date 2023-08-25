<?php
namespace App\Models;
class UserDetails extends BaseModel {
    protected $fillable = ['company_id','user_id','avatar','bio','date_of_birth'];
    public function user() { return $this->belongsTo(User::class); }
}
