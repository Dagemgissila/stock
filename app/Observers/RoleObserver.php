<?php
namespace App\Observers;
use App\Models\Role;
class RoleObserver {
    public function saved(Role $r): void { \Cache::forget('roles_'.$r->company_id); }
    public function deleted(Role $r): void { $r->permissions()->detach(); }
}
