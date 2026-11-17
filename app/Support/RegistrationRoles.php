<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

class RegistrationRoles
{
    public static function available()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->get()
            ->reject(fn (Role $role) => in_array(mb_strtolower(trim($role->name)), ['super administrator', 'super-admin'], true));
    }

    public static function isVolunteer(Role $role): bool
    {
        return (bool) preg_match('/\bvolunteer\b/i', $role->name);
    }
}
