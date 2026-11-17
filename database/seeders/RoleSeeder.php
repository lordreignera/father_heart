<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $this->assertNewDatabase();
        $this->call(PermissionsSeeder::class);

        $permissions = Permission::where('guard_name', 'web')->get();

        // Preserve the role seeded in the first Laravel setup as a working alias.
        foreach (['Super Administrator', 'super-admin'] as $name) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
