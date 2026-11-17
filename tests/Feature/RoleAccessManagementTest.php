<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        return $admin;
    }

    public function test_role_directory_counts_and_filters_staff_without_guard_error(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Intake Officer', 'guard_name' => 'web']);
        $role->givePermissionTo('manage-people');
        User::factory()->create()->assignRole($role);
        Role::create(['name' => 'Empty Role', 'guard_name' => 'web']);
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->assertSee('Intake Officer')
            ->assertViewHas('roles', fn ($roles) => (int) $roles->firstWhere('name', 'Intake Officer')->users_count === 1);
        $this->get(route('admin.roles.index', ['q' => 'manage-people', 'users' => 'with']))->assertOk()->assertSee('Intake Officer')->assertDontSee('Empty Role');
        $this->get(route('admin.roles.index', ['users' => 'without']))->assertOk()->assertSee('Empty Role')->assertDontSee('Intake Officer');
    }

    public function test_super_admin_can_manage_multiple_permissions_and_assign_multiple_roles(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Intake', 'guard_name' => 'web']);
        $other = Role::create(['name' => 'Inventory', 'guard_name' => 'web']);
        $staff = User::factory()->create();
        $oldHash = $staff->password;
        $this->actingAs($admin)->get(route('admin.permissions.index', ['role' => $role->id]))->assertOk()->assertSee('route-admin-people-index');
        $this->put(route('admin.roles.permissions.update', $role), ['permissions' => ['access-admin', 'route-admin-people-index']])->assertRedirect();
        $this->assertEqualsCanonicalizing(['access-admin', 'route-admin-people-index'], $role->fresh()->permissions->pluck('name')->all());
        $this->put(route('admin.users.update', $staff), ['name' => $staff->name, 'email' => $staff->email, 'roles' => [$role->id, $other->id]])->assertRedirect();
        $this->assertTrue($staff->fresh()->hasAllRoles(['Intake', 'Inventory']));
        $this->assertSame($oldHash, $staff->fresh()->password);
        $this->put(route('admin.roles.permissions.update', $role), [])->assertRedirect();
        $this->assertCount(0, $role->fresh()->permissions);
        $this->put(route('admin.roles.permissions.update', Role::findByName('Super Administrator', 'web')), [])->assertForbidden();
        $this->delete(route('admin.roles.destroy', $other))->assertSessionHasErrors('role');
    }

    public function test_managers_cannot_grant_or_change_permissions_beyond_their_own(): void
    {
        $this->admin();
        $manager = User::factory()->create();
        $manager->givePermissionTo(['access-admin', 'route-admin-permissions-index', 'route-admin-roles-permissions-update', 'route-admin-users-update', 'route-admin-permissions-create']);
        $role = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $elevated = Role::create(['name' => 'Inventory Officer', 'guard_name' => 'web']);
        $elevated->givePermissionTo('route-admin-inventory-index');
        $staff = User::factory()->create();
        $privileged = User::factory()->create()->assignRole($elevated);
        $this->actingAs($manager)->get(route('admin.permissions.index', ['role' => $role->id]))->assertOk();
        $this->put(route('admin.roles.permissions.update', $role), ['permissions' => ['route-admin-inventory-index']])->assertSessionHasErrors('permissions.0');
        $this->put(route('admin.roles.permissions.update', $elevated), ['permissions' => []])->assertForbidden();
        $this->put(route('admin.users.update', $staff), ['name' => $staff->name, 'email' => $staff->email, 'roles' => [$elevated->id]])->assertSessionHasErrors('roles.0');
        $this->put(route('admin.users.update', $privileged), ['name' => $privileged->name, 'email' => $privileged->email, 'password' => 'new-password-long', 'password_confirmation' => 'new-password-long'])->assertForbidden();
        $this->get(route('admin.permissions.create'))->assertForbidden();
    }

    public function test_role_modal_creates_without_permissions_and_name_edit_preserves_existing_permissions(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->assertSee('id="role-create"', false);
        $this->post(route('admin.roles.store'), ['_modal' => 'role-create', 'name' => 'Care'])->assertRedirect(route('admin.roles.index'));
        $role = Role::findByName('Care', 'web');
        $this->assertCount(0, $role->permissions);
        $role->givePermissionTo(['access-admin', 'route-admin-people-index']);
        $this->get(route('admin.roles.index'))->assertSee('Add permissions')->assertSee('id="role-edit-'.$role->id.'"', false);
        $this->put(route('admin.roles.update', $role), ['name' => 'Care Team', '_role_name_only' => 1])->assertRedirect();
        $this->assertEqualsCanonicalizing(['access-admin', 'route-admin-people-index'], $role->fresh()->permissions->pluck('name')->all());
        $this->get(route('admin.roles.create'))->assertOk()->assertDontSee('name="permissions[]"', false);
    }

    public function test_permission_editor_groups_individual_routes_and_keeps_existing_selection(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $role->givePermissionTo('route-admin-people-index');
        $this->actingAs($admin)->get(route('admin.permissions.index', ['role' => $role->id]))->assertOk()
            ->assertSee('data-permission-search', false)->assertSee('data-permission-select-all', false)
            ->assertSee('data-module-name="People"', false)->assertSee('data-module-name="Inventory"', false)
            ->assertSee('value="route-admin-people-index" checked', false)
            ->assertSee('View people list')->assertSee('Delete people')
            ->assertSee('Open new assessment form')->assertSee('Save new assessment')
            ->assertDontSee('>route-admin-people-index<', false)->assertDontSee('GET /admin/people');
        $this->get(route('admin.roles.index', ['q' => 'View people list']))->assertOk()->assertSee('Care')
            ->assertSee('View people list')->assertDontSee('>route-admin-people-index<', false);
        $this->put(route('admin.roles.permissions.update', $role), ['permissions' => ['route-admin-people-index', 'route-admin-inventory-index']])->assertRedirect();
        $this->assertEqualsCanonicalizing(['route-admin-people-index', 'route-admin-inventory-index'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_staff_account_details_show_friendly_permission_names(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $role->givePermissionTo(['route-admin-assessments-create', 'route-admin-assessments-store', 'route-admin-assessment-photos-show']);
        $staff = User::factory()->create()->assignRole($role);
        $this->actingAs($admin)->get(route('admin.users.show', $staff))->assertOk()
            ->assertSee('Open new assessment form')->assertSee('Save new assessment')->assertSee('View assessment photos')
            ->assertDontSee('route-admin-assessments-create');
    }
}
