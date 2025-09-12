<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Traits\UserTraits;

/**
 * Class User
 *
 * Represents a staff member or administrator account in the system.
 * Users belong to a company (tenant), have one or more roles which
 * define their permissions, and may be assigned to specific warehouses.
 *
 * Authentication uses JWT tokens via php-open-source-saver/jwt-auth.
 * The User model implements JWTSubject to integrate with the JWT library.
 *
 * @property int         $id
 * @property int|null    $company_id
 * @property int|null    $created_by
 * @property string      $name
 * @property string      $email
 * @property string      $password        (hidden from serialization)
 * @property string|null $phone
 * @property string|null $profile_image
 * @property string|null $tax_number
 * @property bool        $is_superadmin
 * @property bool        $status
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 *
 * @property-read Company|null  $company
 * @property-read string        $full_name
 * @property-read string        $avatar_url
 * @property-read array         $permission_names
 *
 * @package App\Models
 */
class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes, HasFactory, Notifiable, UserTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'email',
        'password',
        'phone',
        'profile_image',
        'tax_number',
        'is_superadmin',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * Password and remember_token are never included in API responses.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_superadmin'     => 'boolean',
        'status'            => 'boolean',
        'deleted_at'        => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = [
        'full_name',
        'avatar_url',
    ];

    // ─── JWT Interface Implementation ────────────────────────────────────────

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     *
     * Required by JWTSubject interface. Returns the primary key value.
     *
     * @return mixed  The user's primary key
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return custom claims to add to the JWT payload.
     *
     * These additional claims are embedded in the JWT token and can be
     * read by the frontend or middleware without a database lookup.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'company_id' => $this->company_id,
            'role'       => $this->roles()->first()?->name ?? 'staff',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get the company this user belongs to.
     *
     * @return BelongsTo<Company, User>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this account.
     *
     * @return BelongsTo<User, User>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all roles assigned to this user.
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Get the warehouses this user is assigned to work in.
     *
     * @return BelongsToMany<Warehouse>
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'user_warehouses',
            'user_id',
            'warehouse_id'
        );
    }

    /**
     * Get the extended profile details for this user.
     *
     * @return HasOne<UserDetails>
     */
    public function details(): HasOne
    {
        return $this->hasOne(UserDetails::class);
    }

    /**
     * Get all expenses created by this user.
     *
     * @return HasMany<Expense>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all payments processed by this user (as staff).
     *
     * @return HasMany<Payment>
     */
    public function processedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'staff_user_id');
    }

    /**
     * Get all orders processed by this user (as staff).
     *
     * @return HasMany<Order>
     */
    public function processedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'staff_user_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Get the user's display name.
     *
     * Returns the name field. Could be extended to combine
     * first_name + last_name in future versions.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the URL to the user's avatar image.
     *
     * Returns the uploaded profile image URL if one exists,
     * otherwise returns a default avatar placeholder.
     *
     * @return string  Absolute URL to avatar image
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('uploads/users/' . $this->profile_image);
        }

        return asset('images/user.png');
    }

    // ─── Permission Methods ───────────────────────────────────────────────────

    /**
     * Check if this user has a specific permission.
     *
     * Traverses the user's assigned roles and their permissions
     * to check if the required permission exists. Results are
     * cached per user to avoid repeated database queries.
     *
     * @param  string  $permission  Permission name (e.g. 'products-view')
     * @return bool    True if user has the permission through any assigned role
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_superadmin) {
            return true; // Super admins bypass all permission checks
        }

        $permissions = $this->getPermissionNames();

        return in_array($permission, $permissions);
    }

    /**
     * Check if this user has all of the given permissions.
     *
     * @param  array  $permissions  Array of permission names
     * @return bool   True only if user has every permission listed
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this user has any of the given permissions.
     *
     * @param  array  $permissions  Array of permission names
     * @return bool   True if user has at least one permission listed
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this user has a specific role.
     *
     * @param  string  $roleName  Role name to check (e.g. 'admin', 'staff')
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if this user has the admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_superadmin || $this->hasRole('admin');
    }

    /**
     * Assign a role to this user, syncing it as the single role.
     *
     * @param  int|string  $roleId  Role ID or role name
     * @return void
     */
    public function assignRole(int|string $roleId): void
    {
        $this->roles()->sync([(int) $roleId]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to active users only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope to non-superadmin users only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNonSuperAdmin(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_superadmin', false);
    }

    /**
     * Scope to users assigned to a specific warehouse.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $warehouseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInWarehouse(\Illuminate\Database\Eloquent\Builder $query, int $warehouseId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        });
    }
}
