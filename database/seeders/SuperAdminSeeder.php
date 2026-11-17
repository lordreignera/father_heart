<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class SuperAdminSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $name = trim((string) config('admin.name'));
        $email = trim((string) config('admin.email'));
        $password = (string) config('admin.password');

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Set SUPER_ADMIN_NAME and a valid SUPER_ADMIN_EMAIL in the local .env before seeding.');
        }

        $this->assertNewDatabase();
        $existing = User::where('email', $email)->first();

        if ($existing && ! $existing->isSuperAdmin()) {
            throw new LogicException('That email belongs to a non-admin user. No account or role was changed; review the account manually.');
        }

        if (! $existing && strlen($password) < 12) {
            throw new InvalidArgumentException('Set SUPER_ADMIN_PASSWORD to at least 12 characters in the local .env before creating the account.');
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $this->call(RoleSeeder::class);

            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
            ]);

            if (! $user->wasRecentlyCreated && ! $user->isSuperAdmin()) {
                throw new LogicException('The admin email was claimed by another user during seeding; no role was assigned.');
            }

            // Rerunning this seeder never changes an existing name or password.
            $user->assignRole('Super Administrator');
        });
    }
}
