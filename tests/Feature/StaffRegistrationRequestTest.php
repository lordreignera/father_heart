<?php

namespace Tests\Feature;

use App\Models\StaffRegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffRegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        return User::factory()->create()->assignRole('Super Administrator');
    }

    private function submit(Role $role, string $email = 'applicant@example.test')
    {
        return $this->post(route('staff-registration.store'), ['name' => 'Applicant', 'email' => $email,
            'password' => 'aB12!xyz', 'password_confirmation' => 'aB12!xyz', 'role_id' => $role->id]);
    }

    public function test_request_is_not_an_account_until_super_admin_approves_a_final_role(): void
    {
        $admin = $this->admin();
        $requested = Role::create(['name' => 'Coordinator', 'guard_name' => 'web']);
        $final = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $final->givePermissionTo('access-admin');
        $this->get('/login')->assertSee('Request staff registration');
        $this->get(route('staff-registration.create'))->assertOk()->assertSee('Coordinator')->assertDontSee('Super Administrator')
            ->assertSee('maxlength="8"', false)->assertSee('aria-controls="password_confirmation"', false);
        $this->submit($requested, 'Applicant@Example.Test')->assertRedirect(route('login'))->assertSessionHas('status', 'Your registration request is awaiting administrator approval. You can sign in after it is approved.');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'applicant@example.test']);
        $registration = StaffRegistrationRequest::where('email', 'applicant@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('aB12!xyz', $registration->password_hash));
        $this->post('/login', ['email' => $registration->email, 'password' => 'wrongpass'])->assertSessionHasErrors(['email' => trans('auth.failed')]);
        $this->post('/login', ['email' => $registration->email, 'password' => 'aB12!xyz'])->assertSessionHasErrors(['email' => 'Your registration request is awaiting administrator approval. You can sign in after it is approved.']);
        $this->assertGuest();
        $this->actingAs($admin)->get(route('admin.registration-requests.index'))->assertOk()->assertSee('Applicant')->assertSee('Coordinator');
        $this->post(route('admin.registration-requests.approve', $registration), ['role_id' => $final->id])->assertRedirect();
        $user = User::where('email', 'applicant@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Staff'));
        $this->assertFalse($user->hasRole('Coordinator'));
        $this->assertTrue(Hash::check('aB12!xyz', $user->password));
        $this->assertNull($registration->fresh()->password_hash);
        $this->assertSame('approved', $registration->fresh()->status);
        $this->post(route('admin.registration-requests.approve', $registration), ['role_id' => $final->id])->assertSessionHasErrors('registration');
        $this->post(route('logout'))->assertRedirect();
        $this->post('/login', ['email' => $user->email, 'password' => 'aB12!xyz'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_super_admin_cannot_review_or_approve_even_with_route_permissions(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $this->submit($role);
        $registration = StaffRegistrationRequest::firstOrFail();
        $manager = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-registration-requests-index', 'route-admin-registration-requests-approve']);
        $this->actingAs($manager)->get(route('admin.registration-requests.index'))->assertForbidden();
        $this->post(route('admin.registration-requests.approve', $registration), ['role_id' => $role->id])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => $registration->email]);
    }

    public function test_super_admin_role_and_duplicate_accounts_cannot_be_requested(): void
    {
        $admin = $this->admin();
        $super = Role::where('name', 'Super Administrator')->firstOrFail();
        $staff = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $this->submit($super)->assertSessionHasErrors('role_id');
        $this->submit($staff, $admin->email)->assertSessionHasErrors('email');
        $this->submit($staff)->assertRedirect(route('login'));
        $this->submit($staff)->assertSessionHasErrors('email');
        $this->assertSame(1, StaffRegistrationRequest::count());
    }

    public function test_rejection_removes_password_and_allows_a_new_request(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $this->submit($role);
        $registration = StaffRegistrationRequest::firstOrFail();
        $this->actingAs($admin)->post(route('admin.registration-requests.reject', $registration))->assertRedirect();
        $this->assertSame('rejected', $registration->fresh()->status);
        $this->assertNull($registration->fresh()->password_hash);
        $this->post(route('logout'));
        $this->submit($role)->assertRedirect(route('login'));
        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_super_admin_can_approve_a_volunteer_with_workspace_access(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Volunteer', 'guard_name' => 'web']);
        $role->givePermissionTo('access-admin');
        $this->submit($role);
        $registration = StaffRegistrationRequest::firstOrFail();
        $this->actingAs($admin)->get(route('admin.registration-requests.index'))->assertOk()
            ->assertDontSee('Volunteer roles cannot yet be approved')->assertDontSee('Volunteer (not yet available)');
        $this->post(route('admin.registration-requests.approve', $registration), ['role_id' => $role->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved', $registration->fresh()->status);
        $this->assertTrue(User::where('email', $registration->email)->firstOrFail()->hasRole('Volunteer'));
    }

    public function test_role_without_workspace_access_cannot_be_approved(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $this->submit($role);
        $registration = StaffRegistrationRequest::firstOrFail();
        $this->actingAs($admin)->post(route('admin.registration-requests.approve', $registration), ['role_id' => $role->id])->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => $registration->email]);
    }

    public function test_approval_will_not_take_over_an_existing_account_created_after_the_request(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $role->givePermissionTo('access-admin');
        $this->submit($role);
        $registration = StaffRegistrationRequest::firstOrFail();
        $existing = User::factory()->create(['email' => $registration->email]);
        $password = $existing->password;
        $this->actingAs($admin)->post(route('admin.registration-requests.approve', $registration), ['role_id' => $role->id])->assertSessionHasErrors('registration');
        $this->assertSame($password, $existing->fresh()->password);
        $this->assertFalse($existing->fresh()->hasRole('Staff'));
        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_registration_requires_exactly_eight_characters(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        foreach (['1234567', '123456789'] as $password) {
            $this->post(route('staff-registration.store'), ['name' => 'Applicant', 'email' => 'applicant@example.test',
                'password' => $password, 'password_confirmation' => $password, 'role_id' => $role->id])->assertSessionHasErrors('password');
        }
        $this->assertSame(0, StaffRegistrationRequest::count());
        $this->submit($role)->assertRedirect(route('login'));
    }

    public function test_pending_email_blocks_staff_account_creation_and_is_case_insensitive(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        $this->submit($role, 'Applicant@Example.Test')->assertRedirect();
        $this->submit($role, 'APPLICANT@example.test')->assertSessionHasErrors('email');
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Duplicate', 'email' => 'APPLICANT@example.test',
            'password' => 'a-secure-password', 'password_confirmation' => 'a-secure-password', 'roles' => []])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'applicant@example.test']);
        $this->assertSame(1, StaffRegistrationRequest::count());
    }
}
