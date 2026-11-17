<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\DistributionEvent;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\Person;
use App\Models\LegacySourceRecord;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_view_an_event_without_creating_another_table(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $district = District::create(['name' => 'Test District']);
        $subcounty = Subcounty::create(['district_id' => $district->id, 'name' => 'Test Subcounty']);

        $this->actingAs($admin)->get(route('admin.distributions.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.distributions.store'), [
            'name' => 'Test Mobility Mission',
            'event_date' => '2026-11-13',
            'district_id' => $district->id,
            'subcounty_id' => $subcounty->id,
            'status' => 'planned',
        ])->assertRedirect();

        $event = DistributionEvent::firstOrFail();
        $this->assertDatabaseCount('distribution_events', 1);
        $this->assertNull($event->legacy_table);
        $this->actingAs($admin)->get(route('admin.distributions.index'))->assertOk()->assertSee('Test Mobility Mission');
        $this->actingAs($admin)->get(route('admin.distributions.show', $event))->assertOk()->assertSee('Test District');
    }

    public function test_event_modals_and_safe_deletion(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $event = DistributionEvent::create(['name' => 'Mission', 'status' => 'planned']);
        $this->actingAs($admin)->get(route('admin.distributions.index'))->assertOk()
            ->assertSee('event-create-dialog')->assertSee('event-view-'.$event->id)->assertSee('event-edit-'.$event->id)->assertSee('event-delete-'.$event->id);
        $person = Person::create(['full_name' => 'Recipient']);
        $person->assessments()->create(['distribution_event_id' => $event->id, 'status' => 'draft']);
        $this->actingAs($admin)->delete(route('admin.distributions.destroy', $event))->assertSessionHasErrors('event');
        $this->assertDatabaseHas('distribution_events', ['id' => $event->id]);
        $empty = DistributionEvent::create(['name' => 'Empty', 'status' => 'planned']);
        $this->actingAs(User::factory()->create())->delete(route('admin.distributions.destroy', $empty))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.distributions.destroy', $empty))->assertRedirect(route('admin.distributions.index'));
        $this->assertDatabaseMissing('distribution_events', ['id' => $empty->id]);
    }

    public function test_historical_values_are_separate_unverified_and_require_people_access(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('Super Administrator');
        $event = DistributionEvent::create(['name' => 'Historical mission', 'status' => 'historical']);
        $person = Person::create(['full_name' => 'Example recipient']);
        $assessment = $person->assessments()->create(['distribution_event_id' => $event->id, 'status' => 'legacy_unverified', 'device_requested' => 'Cane']);
        LegacySourceRecord::create(['source_key' => hash('sha256', 'mission'), 'source_database' => 'fathersheart', 'source_table' => 'old_mission', 'source_row_id' => '1',
            'checksum' => hash('sha256', 'original'), 'raw_payload' => ['givemobility' => 'gen2_m_17', 'date' => '08/10/2020'], 'status' => 'imported', 'mapped_type' => 'assessment', 'mapped_id' => $assessment->id]);
        $this->actingAs($admin)->get(route('admin.distributions.show', $event))->assertOk()
            ->assertSee('Device considered')->assertSee('Cane')->assertSee('Historical device (unverified)')->assertSee('gen2_m_17')
            ->assertSee('Historical recorded date (unverified)')->assertSee('08/10/2020')->assertSee('neither value confirms a device was delivered');
        $limited = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-distributions-show']);
        $response = $this->actingAs($limited)->get(route('admin.distributions.show', $event))->assertOk()
            ->assertDontSee('Historical device (unverified)')->assertDontSee('gen2_m_17')->assertDontSee('08/10/2020')
            ->assertDontSee(route('admin.assessments.show', $assessment));
        $this->assertNull($response->viewData('assessments'));
        $this->assertSame('Cane', $assessment->fresh()->device_requested);
        $this->assertNull($assessment->fresh()->assessed_on);
    }

    public function test_event_assessments_remain_accessible_across_pages(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('Super Administrator');
        $event = DistributionEvent::create(['name' => 'Large event', 'status' => 'historical']);
        for ($i = 1; $i <= 26; $i++) {
            $person = Person::create(['full_name' => 'Person '.$i]);
            $assessment = $person->assessments()->create(['distribution_event_id' => $event->id, 'status' => 'legacy_unverified']);
            LegacySourceRecord::create(['source_key' => hash('sha256', 'row-'.$i), 'source_database' => 'fathersheart', 'source_table' => 'mission', 'source_row_id' => (string) $i,
                'checksum' => hash('sha256', 'original-'.$i), 'raw_payload' => ['givemobility' => 'Device '.$i, 'date' => '08/10/2020'], 'status' => 'imported', 'mapped_type' => 'assessment', 'mapped_id' => $assessment->id]);
        }
        $this->actingAs($admin)->get(route('admin.distributions.show', $event))->assertOk()
            ->assertViewHas('assessments', fn ($rows) => $rows->total() === 26 && $rows->count() === 25);
        $this->get(route('admin.distributions.show', [$event, 'page' => 2]))->assertOk()
            ->assertViewHas('assessments', fn ($rows) => $rows->total() === 26 && $rows->count() === 1 && $rows->first()->legacySource !== null);
    }
}
