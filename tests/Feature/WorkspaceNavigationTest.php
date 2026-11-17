<?php

namespace Tests\Feature;

use App\Models\LegacySourceRecord;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_events_render_as_a_table_without_exposing_unlisted_source_fields(): void
    {
        $this->seed(RoleSeeder::class);
        $record = LegacySourceRecord::create(['source_key' => hash('sha256', 'event'), 'source_database' => 'fathersheart', 'source_table' => 'distribution',
            'source_row_id' => '42', 'checksum' => hash('sha256', 'original'), 'raw_payload' => ['id' => 42, 'distribution' => 'Historical <Mission>', 'district' => 'Kampala', 'secret' => 'never-display'], 'status' => 'staged']);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-historical-events']);
        $this->actingAs($user)->get(route('admin.historical.index', 'events'))->assertOk()->assertSee('<table', false)
            ->assertSee('Event name')->assertSee('Historical &lt;Mission&gt;', false)->assertSee('Kampala')->assertSee((string) $record->source_row_id)
            ->assertDontSee('never-display')->assertSee('not verified event dates or locations');
        $this->get(route('admin.historical.index', 'locations'))->assertForbidden();
    }

    public function test_sidebar_groups_respect_route_permissions_and_guide_is_available(): void
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-users-index']);
        $this->actingAs($staff)->get(route('admin.users.index'))->assertOk()->assertSee('User management')
            ->assertSee('Staff accounts')->assertSee('User guide')->assertDontSee('Historical team')
            ->assertDontSee('Distribution events')->assertDontSee('Stock management');
        $this->get(route('user-guide'))->assertOk()->assertSee('Register a person')->assertSee('Staff, roles and permissions')->assertSee('access-admin');
    }

    public function test_guide_requires_workspace_access(): void
    {
        $this->get(route('user-guide'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('user-guide'))->assertForbidden();
    }
}
