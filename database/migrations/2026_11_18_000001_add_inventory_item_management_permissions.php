<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $names = ['route-admin-inventory-items-update', 'route-admin-inventory-items-destroy'];
        foreach ($names as $name) DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $names)->pluck('id');
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['Super Administrator', 'super-admin'])->pluck('id') as $role) {
            foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void { /* Keep any permission assignments. */ }
};
