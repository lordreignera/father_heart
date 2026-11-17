<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleAccess
{
    public static function canManage(User $actor, Role $role): bool
    {
        if ($actor->isSuperAdmin()) return true;
        $allowed = $actor->getAllPermissions()->pluck('name');
        return $role->permissions->every(fn ($permission) => $allowed->contains($permission->name));
    }

    public static function assignablePermissions(User $actor)
    {
        return $actor->isSuperAdmin() ? null : $actor->getAllPermissions()->pluck('name')->all();
    }

    public static function canManageUser(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) return true;
        if ($target->isSuperAdmin()) return false;
        $allowed = $actor->getAllPermissions()->pluck('name');
        return $target->getAllPermissions()->every(fn ($permission) => $allowed->contains($permission->name));
    }
}
