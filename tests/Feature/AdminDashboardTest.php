<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_regular_users_cannot_open_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_super_admin_can_open_the_dashboard(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['name' => 'Cedar']);
        $admin->assignRole('Super Administrator');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Welcome back, Cedar.')
            ->assertSee('People &amp; registration', false)
            ->assertSee('No legacy records imported yet');

        $this->assertTrue($admin->can('access-admin'));
    }

    public function test_role_seeder_is_safe_to_run_twice(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('permissions', 7 + count(\App\Support\RoutePermissions::catalogue()));
        $this->assertDatabaseHas('roles', ['name' => 'Super Administrator', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'super-admin', 'guard_name' => 'web']);

        $this->assertCount(7 + count(\App\Support\RoutePermissions::catalogue()), Role::findByName('Super Administrator', 'web')->permissions);
        $this->assertCount(7 + count(\App\Support\RoutePermissions::catalogue()), Role::findByName('super-admin', 'web')->permissions);
    }

    public function test_role_seeder_grants_new_permissions_to_super_admin(): void
    {
        $this->seed(RoleSeeder::class);
        Permission::findOrCreate('new-module', 'web');
        $this->seed(RoleSeeder::class);

        $this->assertTrue(Role::findByName('Super Administrator', 'web')->hasPermissionTo('new-module'));
        $this->assertTrue(Role::findByName('super-admin', 'web')->hasPermissionTo('new-module'));
    }

    public function test_admin_seeder_creates_a_hashed_account_and_role(): void
    {
        config()->set('admin.name', 'Cedar');
        config()->set('admin.email', 'cedar@example.test');
        config()->set('admin.password', 'long-test-password-123');

        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'cedar@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('long-test-password-123', $admin->password));
        $this->assertTrue($admin->hasRole('Super Administrator'));
        $this->assertFalse($admin->hasRole('super-admin'));
    }

    public function test_reseeding_keeps_the_existing_admin_password(): void
    {
        config()->set('admin.name', 'Cedar');
        config()->set('admin.email', 'cedar@example.test');
        config()->set('admin.password', 'long-test-password-123');
        $this->seed(SuperAdminSeeder::class);

        config()->set('admin.password', 'different-password-456');
        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'cedar@example.test')->firstOrFail();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('long-test-password-123', $admin->password));
        $this->assertTrue($admin->hasRole('Super Administrator'));
    }

    public function test_admin_seeder_requires_credentials(): void
    {
        config()->set('admin.email', null);
        config()->set('admin.password', null);

        $this->expectException(InvalidArgumentException::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_admin_seeder_does_not_promote_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'cedar@example.test']);
        config()->set('admin.name', 'Cedar');
        config()->set('admin.email', 'cedar@example.test');
        config()->set('admin.password', 'long-test-password-123');

        try {
            $this->seed(SuperAdminSeeder::class);
            $this->fail('Expected the seeder to refuse an existing email.');
        } catch (LogicException) {
            $this->assertFalse($user->fresh()->isSuperAdmin());
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_default_seeding_without_credentials_does_not_create_a_user(): void
    {
        config()->set('admin.email', null);
        config()->set('admin.password', null);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('roles', ['name' => 'Super Administrator', 'guard_name' => 'web']);
    }

    public function test_default_seeding_creates_admin_when_credentials_are_configured(): void
    {
        config()->set('admin.name', 'Cedar');
        config()->set('admin.email', 'cedar@example.test');
        config()->set('admin.password', 'long-test-password-123');

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'cedar@example.test')->firstOrFail()->isSuperAdmin());
    }

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
