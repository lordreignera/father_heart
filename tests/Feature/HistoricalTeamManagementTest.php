<?php

namespace Tests\Feature;

use App\Models\HistoricalTeamProfile;
use App\Models\LegacySourceRecord;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistoricalTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private function record(string $table = 'staff'): LegacySourceRecord
    {
        return LegacySourceRecord::create(['source_key' => hash('sha256', $table), 'source_database' => 'fathersheart', 'source_table' => $table,
            'source_row_id' => '4', 'checksum' => hash('sha256', 'original'), 'raw_payload' => ['id' => 4, 'firstname' => 'Alex', 'lastname' => 'Example', 'username' => 'old-alex', 'phone' => '0700000000', 'password' => 'never-expose-old-hash'], 'status' => 'staged']);
    }

    private function superAdmin(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Administrator');
        return $user;
    }

    public function test_table_and_modals_are_private_and_never_display_old_hashes(): void
    {
        $record = $this->record();
        $this->get(route('admin.historical.team.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('admin.historical.team.index'))->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('admin.historical.team.index'))->assertOk()
            ->assertSee('Alex Example')->assertSee('old-alex')->assertSee('team-view-'.$record->id)->assertSee('team-edit-'.$record->id)
            ->assertSee('team-delete-'.$record->id)->assertSee('team-account-'.$record->id)->assertDontSee('never-expose-old-hash');
    }

    public function test_super_admin_can_create_account_with_new_password_and_no_automatic_super_role(): void
    {
        $record = $this->record();
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'care staff', 'guard_name' => 'web']);
        $data = ['name' => 'Alex Verified', 'email' => 'alex@example.test', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password', 'roles' => [$role->id]];
        $this->actingAs($admin)->post(route('admin.historical.team.account', $record), $data)->assertRedirect();
        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertFalse(Hash::check('never-expose-old-hash', $user->password));
        $this->assertTrue($user->hasRole('care staff'));
        $this->assertFalse($user->isSuperAdmin());
        $this->assertSame($user->id, HistoricalTeamProfile::where('legacy_source_record_id', $record->id)->firstOrFail()->user_id);
        $this->assertSame('never-expose-old-hash', $record->fresh()->raw_payload['password']);
        $this->actingAs($admin)->post(route('admin.historical.team.account', $record), array_merge($data, ['email' => 'another@example.test']))->assertSessionHasErrors('record');
        $this->assertDatabaseCount('historical_team_profiles', 1);
    }

    public function test_only_super_admin_can_change_team_details_or_create_accounts(): void
    {
        $record = $this->record();
        $admin = $this->superAdmin();
        $manager = User::factory()->create();
        $manager->givePermissionTo(['access-admin', 'route-admin-historical-team-index', 'route-admin-historical-team-update', 'route-admin-historical-team-account', 'route-admin-historical-team-archive']);
        $this->actingAs($manager)->get(route('admin.historical.team.index'))->assertOk()->assertDontSee('team-edit-'.$record->id);
        $this->actingAs($manager)->put(route('admin.historical.team.update', $record), ['name' => 'Tampered'])->assertForbidden();
        $this->actingAs($manager)->post(route('admin.historical.team.account', $record), [])->assertForbidden();
        $this->actingAs($manager)->delete(route('admin.historical.team.archive', $record))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.historical.team.update', $record), ['name' => 'Alex Updated', 'legacy_username' => 'updated', 'phone' => '123'])->assertRedirect();
        $this->assertDatabaseHas('historical_team_profiles', ['legacy_source_record_id' => $record->id, 'name' => 'Alex Updated']);
        $this->assertSame('old-alex', $record->fresh()->raw_payload['username']);
        $this->actingAs($admin)->put(route('admin.historical.team.update', $record), ['name' => 'Alex Updated', 'legacy_username' => '', 'phone' => ''])->assertRedirect();
        $this->actingAs($admin)->get(route('admin.historical.team.index'))->assertDontSee('old-alex')->assertDontSee('0700000000');
        $other = $this->record('distribution');
        $this->actingAs($admin)->put(route('admin.historical.team.update', $other), ['name' => 'Wrong source'])->assertNotFound();
    }

    public function test_duplicate_email_admin_role_and_weak_password_are_rejected(): void
    {
        $record = $this->record();
        $admin = $this->superAdmin();
        $adminRole = Role::where('name', 'Super Administrator')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.historical.team.account', $record), ['name' => 'Alex', 'email' => $admin->email, 'password' => 'short', 'password_confirmation' => 'short', 'roles' => [$adminRole->id]])
            ->assertSessionHasErrors(['email', 'password', 'roles.0']);
        $this->assertDatabaseCount('historical_team_profiles', 0);
    }

    public function test_password_reset_and_recoverable_archive_keep_the_login_account(): void
    {
        $record = $this->record();
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post(route('admin.historical.team.account', $record), ['name' => 'Alex', 'email' => 'alex@example.test', 'password' => 'first-secure-password', 'password_confirmation' => 'first-secure-password'])->assertRedirect();
        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.historical.team.password', $record), ['password' => 'second-secure-password', 'password_confirmation' => 'second-secure-password'])->assertRedirect();
        $this->assertTrue(Hash::check('second-secure-password', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->remember_token);
        $this->actingAs($admin)->delete(route('admin.historical.team.archive', $record))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('legacy_source_records', ['id' => $record->id]);
        $this->actingAs($admin)->get(route('admin.historical.team.index'))->assertDontSee('alex@example.test');
        $this->actingAs($admin)->get(route('admin.historical.team.index', ['archived' => 1]))->assertSee('alex@example.test');
        $this->actingAs($admin)->post(route('admin.historical.team.restore', $record))->assertRedirect();
        $this->assertNull(HistoricalTeamProfile::where('legacy_source_record_id', $record->id)->firstOrFail()->archived_at);
    }

    public function test_password_reset_removes_only_the_linked_users_database_sessions(): void
    {
        $record = $this->record();
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post(route('admin.historical.team.account', $record), ['name' => 'Alex', 'email' => 'alex@example.test', 'password' => 'first-secure-password', 'password_confirmation' => 'first-secure-password'])->assertRedirect();
        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $user->createToken('historical-login');
        DB::table('sessions')->insert([
            ['id' => 'target-session', 'user_id' => $user->id, 'payload' => 'payload', 'last_activity' => time()],
            ['id' => 'admin-session', 'user_id' => $admin->id, 'payload' => 'payload', 'last_activity' => time()],
        ]);
        config(['session.driver' => 'database', 'session.connection' => null]);
        $this->actingAs($admin)->put(route('admin.historical.team.password', $record), ['password' => 'second-secure-password', 'password_confirmation' => 'second-secure-password'])->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_existing_account_link_requires_confirmation_and_does_not_change_credentials(): void
    {
        $record = $this->record();
        $another = $this->record('volunteer');
        $admin = $this->superAdmin();
        $user = User::factory()->create(['password' => 'existing-safe-password']);
        $before = $user->password;
        $this->actingAs($admin)->post(route('admin.historical.team.link', $record), ['user_id' => $user->id])->assertSessionHasErrors('confirm_identity');
        $this->actingAs($admin)->post(route('admin.historical.team.link', $record), ['user_id' => $user->id, 'confirm_identity' => 1])->assertRedirect();
        $this->assertSame($user->id, HistoricalTeamProfile::where('legacy_source_record_id', $record->id)->firstOrFail()->user_id);
        $this->assertSame($before, $user->fresh()->password);
        $this->actingAs($admin)->post(route('admin.historical.team.link', $another), ['user_id' => $user->id, 'confirm_identity' => 1])->assertSessionHasErrors('user_id');
        $this->actingAs($admin)->post(route('admin.historical.team.link', $another), ['user_id' => $admin->id, 'confirm_identity' => 1])->assertForbidden();
    }
}
