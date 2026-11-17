<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use App\Support\RoutePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $this->assertNewDatabase();

        foreach (array_merge([
            'access-admin',
            'manage-staff',
            'manage-roles',
            'manage-people',
            'manage-distributions',
            'manage-inventory',
            'view-reports',
        ], array_keys(RoutePermissions::catalogue())) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
