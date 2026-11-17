<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\LegacySourceRecord;
use App\Models\User;
use App\Support\RoutePermissions;
use App\Http\Middleware\AuthorizeAdminRoute;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndividualRoutePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_role_cannot_create_edit_delete_or_export_even_by_direct_url(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::create(['name' => 'Read only', 'guard_name' => 'web']);
        $role->givePermissionTo(['access-admin', 'route-admin-people-index']);
        $user = User::factory()->create()->assignRole($role);
        $person = Person::create(['full_name' => 'Example']);
        $this->actingAs($user)->get('/admin/people')->assertOk()->assertSee('Example')->assertDontSee('Register person')
            ->assertDontSee('person-edit-'.$person->id)->assertDontSee('person-delete-'.$person->id)->assertDontSee('Export CSV');
        $this->get('/admin/people/export')->assertForbidden();
        $this->post('/admin/people', ['full_name' => 'Unauthorised'])->assertForbidden();
        $this->put(route('admin.people.update', $person), ['full_name' => 'Changed'])->assertForbidden();
        $this->delete(route('admin.people.destroy', $person))->assertForbidden();
        $this->get(route('admin.people.show', $person))->assertForbidden();
        $this->assertSame('Example', $person->fresh()->full_name);
    }

    public function test_old_broad_key_does_not_override_individual_permissions(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'manage-people', 'route-admin-people-index']);
        $person = Person::create(['full_name' => 'Example']);
        $this->actingAs($user)->get('/admin/people')->assertOk();
        $this->delete(route('admin.people.destroy', $person))->assertForbidden();
    }

    public function test_save_without_detail_permission_redirects_to_the_allowed_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-people-index', 'route-admin-people-store']);
        $this->actingAs($user)->post(route('admin.people.store'), ['full_name' => 'New person'])->assertRedirect(route('admin.people.index'))->assertSessionHas('status');
        $this->assertDatabaseHas('people', ['full_name' => 'New person']);
    }

    public function test_catalogue_contains_every_named_admin_route_and_special_history_categories(): void
    {
        $this->seed(RoleSeeder::class);
        $names = RoutePermissions::catalogue();
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name && str_starts_with($name, 'admin.') && $name !== 'admin.historical.index') {
                $key = RoutePermissions::key($name);
                $this->assertArrayHasKey($key, $names);
                $this->assertContains(AuthorizeAdminRoute::class, $route->gatherMiddleware());
                $this->assertDatabaseHas('permissions', ['name' => $key, 'guard_name' => 'web']);
            }
        }
        $this->assertArrayHasKey('route-admin-historical-media', $names);
        $this->assertArrayHasKey('route-admin-historical-locations', $names);
    }

    public function test_person_page_does_not_expose_assessment_history_without_its_permission(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-people-show']);
        $person = Person::create(['full_name' => 'Example']);
        $person->assessments()->create(['status' => 'draft', 'intake_code' => 'PRIVATE-CODE']);
        $this->actingAs($user)->get(route('admin.people.show', $person))->assertOk()
            ->assertDontSee('Assessment history')->assertDontSee('PRIVATE-CODE');
    }

    public function test_granular_history_access_does_not_grant_other_categories_or_super_admin_actions(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-historical-locations', 'route-admin-historical-team-index', 'route-admin-historical-team-account']);
        $record = LegacySourceRecord::create(['source_key' => hash('sha256', 'staff'), 'source_database' => 'fathersheart', 'source_table' => 'staff', 'source_row_id' => '1', 'checksum' => hash('sha256', 'staff'), 'raw_payload' => ['id' => 1, 'username' => 'old'], 'status' => 'staged']);
        $this->actingAs($user)->get('/admin/historical/locations')->assertOk();
        $this->get('/admin/historical/media')->assertForbidden();
        $this->get('/admin/historical/team')->assertOk();
        $this->post(route('admin.historical.team.account', $record))->assertForbidden();
    }
}
