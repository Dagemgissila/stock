<?php
namespace App\Classes;

use App\Models\Permission;
use App\Models\Role;

class PermsSeed
{
    public static function seedForCompany(int $companyId): void
    {
        $permissions = Permission::all();

        // Give admin role all permissions
        $adminRole = Role::where('company_id', $companyId)->where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync($permissions->pluck('id')->toArray());
        }

        // Give staff role view-only permissions
        $staffRole = Role::where('company_id', $companyId)->where('name', 'staff')->first();
        if ($staffRole) {
            $viewPerms = $permissions->filter(fn($p) => str_ends_with($p->name, '-view'));
            $staffRole->permissions()->sync($viewPerms->pluck('id')->toArray());
        }
    }
}
