<?php
namespace App\Models;
class Role extends BaseModel {
    protected $fillable = ['company_id','name','display_name','description'];
    public function users()       { return $this->belongsToMany(User::class,'role_user'); }
    public function permissions() { return $this->belongsToMany(Permission::class,'permission_role'); }
}
