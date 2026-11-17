<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffAccountModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        return User::factory()->create()->assignRole('Super Administrator');
    }

    public function test_table_has_modals_and_role_dropdown_and_create_assigns_multiple_roles(): void
    {
        $admin = $this->admin();
        $staff = User::factory()->create();
        $care = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $inventory = Role::create(['name' => 'Inventory', 'guard_name' => 'web']);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('staff-create')
            ->assertSee('staff-view-'.$staff->id)->assertSee('staff-edit-'.$staff->id)->assertSee('staff-delete-'.$staff->id)
            ->assertSee('Select roles')->assertSee('name="roles[]"', false)->assertDontSee('staff-delete-'.$admin->id);
        $this->post(route('admin.users.store'), ['_modal' => 'staff-create', 'name' => 'New Member', 'email' => 'new-member@example.test',
            'password' => 'a-very-secure-password', 'password_confirmation' => 'a-very-secure-password', 'roles' => [$care->id, $inventory->id]])->assertRedirect(route('admin.users.index'));
        $created = User::where('email', 'new-member@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-very-secure-password', $created->password));
        $this->assertTrue($created->hasAllRoles(['Care', 'Inventory']));
    }

    public function test_edit_modal_keeps_password_and_replaces_roles_and_delete_requires_confirmation_action(): void
    {
        $admin = $this->admin();
        $staff = User::factory()->create();
        $oldHash = $staff->password;
        $role = Role::create(['name' => 'Volunteer', 'guard_name' => 'web']);
        $this->actingAs($admin)->put(route('admin.users.update', $staff), ['_modal' => 'staff-edit-'.$staff->id, 'name' => 'Updated', 'email' => $staff->email, 'roles' => [$role->id]])
            ->assertRedirect(route('admin.users.index'));
        $this->assertSame($oldHash, $staff->fresh()->password);
        $this->assertTrue($staff->fresh()->hasRole('Volunteer'));
        $this->delete(route('admin.users.destroy', $staff))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->delete(route('admin.users.destroy', $admin))->assertStatus(422);
    }

    public function test_invalid_create_reopens_modal_and_does_not_repopulate_passwords(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Volunteer', 'guard_name' => 'web']);
        $this->actingAs($admin)->from(route('admin.users.index'))->post(route('admin.users.store'), ['_modal' => 'staff-create', 'name' => 'Someone', 'email' => $admin->email,
            'password' => 'a-very-secure-password', 'password_confirmation' => 'a-very-secure-password', 'roles' => [$role->id]])->assertSessionHasErrors('email');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('id="staff-create"', false)->assertSee('value="Someone"', false)->assertDontSee('a-very-secure-password');
    }

    public function test_lower_privileged_managers_cannot_edit_or_delete_elevated_users(): void
    {
        $admin = $this->admin();
        $manager = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-users-index', 'route-admin-users-update', 'route-admin-users-destroy']);
        $elevatedRole = Role::create(['name' => 'Inventory Manager', 'guard_name' => 'web']);
        $elevatedRole->givePermissionTo('route-admin-inventory-index');
        $target = User::factory()->create()->assignRole($elevatedRole);
        $this->actingAs($manager)->get(route('admin.users.index'))->assertOk()->assertSee('staff-view-'.$target->id)
            ->assertDontSee('staff-edit-'.$target->id)->assertDontSee('staff-delete-'.$target->id)
            ->assertDontSee('staff-edit-'.$admin->id);
        $this->put(route('admin.users.update', $target), [])->assertForbidden();
        $this->delete(route('admin.users.destroy', $target))->assertForbidden();
    }

    public function test_password_reset_and_role_removal_revoke_only_the_affected_accounts_sessions_and_tokens(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $staff = User::factory()->create()->assignRole($role);
        $staff->createToken('staff-token');
        $admin->createToken('admin-token');
        DB::table('sessions')->insert([
            ['id' => 'staff-session', 'user_id' => $staff->id, 'payload' => 'payload', 'last_activity' => time()],
            ['id' => 'other-session', 'user_id' => $admin->id, 'payload' => 'payload', 'last_activity' => time()],
        ]);
        config(['session.driver' => 'database', 'session.connection' => null]);
        $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            '_modal' => 'staff-edit-'.$staff->id, 'name' => $staff->name, 'email' => $staff->email,
            'password' => 'a-new-secure-password', 'password_confirmation' => 'a-new-secure-password', 'roles' => [],
        ])->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('sessions', ['id' => 'staff-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        $this->assertSame(0, $staff->tokens()->count());
        $this->assertSame(1, $admin->tokens()->count());
    }

    public function test_removing_a_role_permission_revokes_assigned_users_sessions_and_tokens(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Care', 'guard_name' => 'web']);
        $role->givePermissionTo('route-admin-people-index');
        $staff = User::factory()->create()->assignRole($role);
        $staff->createToken('staff-token');
        DB::table('sessions')->insert([
            ['id' => 'role-session', 'user_id' => $staff->id, 'payload' => 'payload', 'last_activity' => time()],
            ['id' => 'admin-session', 'user_id' => $admin->id, 'payload' => 'payload', 'last_activity' => time()],
        ]);
        config(['session.driver' => 'database', 'session.connection' => null]);
        $this->actingAs($admin)->put(route('admin.roles.permissions.update', $role), ['permissions' => []])->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'role-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
        $this->assertSame(0, $staff->tokens()->count());
    }
}
