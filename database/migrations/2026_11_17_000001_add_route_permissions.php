<?php

use App\Support\RoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $permissions = array_keys(RoutePermissions::catalogue());
        if (count($permissions) < 10) throw new RuntimeException('Admin routes are not loaded; route permissions were not installed.');
        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
        $adminRoleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['Super Administrator', 'super-admin'])->pluck('id');
        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $permissions)->pluck('id');
        foreach ($adminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Do not delete permissions or staff assignments on rollback.
    }
};
