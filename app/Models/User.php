<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\UserTraits;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes, UserTraits;

    protected $fillable = [
        'company_id','created_by','name','email','password',
        'phone','profile_image','tax_number','is_superadmin','status',
    ];
    protected $hidden = ['password','remember_token'];
    protected $casts  = ['status'=>'boolean','is_superadmin'=>'boolean'];

    public function getJWTIdentifier(): mixed { return $this->getKey(); }
    public function getJWTCustomClaims(): array { return []; }

    public function company()    { return $this->belongsTo(Company::class); }
    public function role()       { return $this->belongsToMany(Role::class,'role_user')->first(); }
    public function roles()      { return $this->belongsToMany(Role::class,'role_user'); }
    public function warehouses() { return $this->belongsToMany(Warehouse::class,'user_warehouses'); }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->with('permissions')->get()
            ->flatMap->permissions->pluck('name')->contains($permission);
    }
}
