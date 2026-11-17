<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UgandaLocationsSeeder::class);
        $this->call(TherapyDisabilityOptionsSeeder::class);
        $this->call(TherapyMusicOptionsSeeder::class);

        // Like EBIMS, a configured first account is created during normal seeding.
        // An unconfigured installation still gets roles, but no default password.
        if (filled(config('admin.email')) || filled(config('admin.password'))) {
            $this->call(SuperAdminSeeder::class);
        }
    }
}
