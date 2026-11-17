<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\DistributionEvent;
use App\Models\Person;
use App\Models\User;
use App\Support\LegacyEventLocations;
use App\Support\LegacyEventPeriod;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyEventDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function registry(int $id, string $event, string $district): void
    {
        $json = json_encode(['id' => $id, 'distribution' => $event, 'district' => $district], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        DB::table('legacy_source_records')->insert(['source_key' => hash('sha256', 'registry'.$id), 'source_database' => 'fathersheart', 'source_table' => 'distribution', 'source_row_id' => (string) $id, 'checksum' => hash('sha256', $json), 'raw_payload' => Crypt::encryptString($json), 'status' => 'staged']);
    }

    public function test_period_labels_do_not_invent_a_day_for_monthly_or_ambiguous_names(): void
    {
        $this->assertSame('19 November 2015', LegacyEventPeriod::describe('2015_19th_november'));
        $this->assertSame('July 2024', LegacyEventPeriod::describe('2024_july__case_variant'));
        $this->assertSame('January / June 2026', LegacyEventPeriod::describe('2026_jan_jun'));
        $this->assertSame('February 2015', LegacyEventPeriod::describe('2015_31st_february'));
        $this->assertSame('2024', LegacyEventPeriod::describe('moroto_2024'));
        $this->assertNull(LegacyEventPeriod::describe('unknown'));
        $this->assertNull(LegacyEventPeriod::describe('2023_2024_may'));
    }

    public function test_location_matching_preserves_conflicts_and_existing_staff_choices(): void
    {
        $kampala = District::create(['name' => 'Kampala']);
        $wakiso = District::create(['name' => 'Wakiso']);
        $a = DistributionEvent::create(['name' => 'A', 'status' => 'historical']);
        $a->forceFill(['legacy_table' => '2020_january'])->save();
        $b = DistributionEvent::create(['name' => 'B', 'status' => 'historical']);
        $b->forceFill(['legacy_table' => '2020_february'])->save();
        $c = DistributionEvent::create(['name' => 'C', 'status' => 'historical', 'district_id' => $wakiso->id]);
        $c->forceFill(['legacy_table' => '2020_march'])->save();
        $this->registry(1, '2020_January ', 'Kampala');
        $this->registry(2, '2020_february', 'Kampala');
        $this->registry(3, '2020_february', 'Wakiso');
        $this->registry(4, '2020_march', 'Kampala');
        $mapper = app(LegacyEventLocations::class);
        $dry = $mapper->match(DB::connection(), 'fathersheart', false);
        $this->assertSame(1, $dry['matched']);
        $this->assertNull($a->fresh()->district_id);
        $result = $mapper->match(DB::connection(), 'fathersheart', true);
        $this->assertSame(1, $result['matched']);
        $this->assertSame(1, $result['conflicting_registry']);
        $this->assertSame(1, $result['existing_location_skipped']);
        $this->assertSame($kampala->id, $a->fresh()->district_id);
        $this->assertNull($b->fresh()->district_id);
        $this->assertSame($wakiso->id, $c->fresh()->district_id);
    }

    public function test_historical_suggestions_are_labelled_and_confirmed_dates_take_precedence(): void
    {
        $this->registry(1, '2020_january', 'Kampala');
        $this->registry(2, '2020_january', 'Wakiso');
        $event = DistributionEvent::create(['name' => 'Historical mission', 'status' => 'historical']);
        $event->forceFill(['legacy_table' => '2020_january'])->save();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $this->actingAs($admin)->get(route('admin.distributions.index'))->assertOk()->assertSee('January 2020')->assertSee('old name; unverified')->assertSee('Kampala / Wakiso')->assertSee('old registry; review');
        $event->update(['event_date' => '2020-01-20']);
        $this->actingAs($admin)->get(route('admin.distributions.show', $event))->assertOk()->assertSee('20 Jan 2020')->assertDontSee('January 2020 (from old name; unverified)');
    }

    public function test_recipient_location_conflict_blocks_automatic_event_match(): void
    {
        District::create(['name' => 'Kampala']);
        $wakiso = District::create(['name' => 'Wakiso']);
        $event = DistributionEvent::create(['name' => 'Historical mission', 'status' => 'historical']);
        $event->forceFill(['legacy_table' => '2020_january'])->save();
        $this->registry(1, '2020_january', 'Kampala');
        $person = Person::create(['full_name' => 'Example']);
        $person->assessments()->create(['distribution_event_id' => $event->id, 'district_id' => $wakiso->id, 'status' => 'legacy_unverified']);
        $stats = app(LegacyEventLocations::class)->match(DB::connection(), 'fathersheart', true);
        $this->assertSame(1, $stats['recipient_conflict']);
        $this->assertNull($event->fresh()->district_id);
    }
}
