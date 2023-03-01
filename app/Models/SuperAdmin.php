<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class SuperAdmin extends Authenticatable implements JWTSubject
{
    protected $fillable = ['name','email','password'];
    protected $hidden   = ['password','remember_token'];

    public function getJWTIdentifier(): mixed { return $this->getKey(); }
    public function getJWTCustomClaims(): array { return ['role'=>'super_admin']; }

    public function companies() { return $this->hasMany(Company::class,'created_by'); }
}
