<?php
namespace App\Traits;
trait UserTraits {
    public function getFullNameAttribute(): string { return $this->name; }
    public function scopeActive($query) { return $query->where('status',1); }
    public function getPermissionNames(): array {
        return $this->roles()->with('permissions')->get()
            ->flatMap->permissions->pluck('name')->unique()->values()->toArray();
    }
}
