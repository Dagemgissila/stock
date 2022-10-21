<?php
namespace App\Models;
class Permission extends BaseModel {
    protected $fillable = ['name','display_name','module'];
    public function roles() { return $this->belongsToMany(Role::class,'permission_role'); }
}
