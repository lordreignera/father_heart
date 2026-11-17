<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\DistributionEvent;
use App\Models\InventoryItem;
use App\Models\LegacySourceRecord;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Administrator');
        return $user;
    }

    public function test_historical_stock_is_only_shown_as_unverified_reference(): void
    {
        LegacySourceRecord::create(['source_key' => hash('sha256', 'stock'), 'source_database' => 'fathersheart', 'source_table' => 'cane', 'source_row_id' => '1', 'checksum' => hash('sha256', 'stock'), 'raw_payload' => ['id' => 1, 'available' => 42], 'status' => 'staged']);
        $this->actingAs($this->admin())->get(route('admin.inventory.index'))->assertOk()
            ->assertSee('Historical stock references')->assertSee('not verified current stock')->assertSee(route('admin.inventory.historical.index'));
        $this->get(route('admin.inventory.historical.index'))->assertOk()->assertSee('cane')->assertSee('42')->assertSee('unverified historical references');
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->actingAs(User::factory()->create())->get(route('admin.inventory.index'))->assertForbidden();
    }

    public function test_historical_stock_filter_export_and_access_cover_all_matching_records(): void
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 30; $i++) {
            LegacySourceRecord::create(['source_key' => hash('sha256', 'cane-'.$i), 'source_database' => 'fathersheart', 'source_table' => 'cane', 'source_row_id' => (string) $i,
                'checksum' => hash('sha256', 'cane-original-'.$i), 'raw_payload' => ['id' => $i, 'date' => '12/11/2020', 'available' => $i, 'notes' => $i === 30 ? '=2+3' : 'Reference'], 'status' => 'staged']);
        }
        LegacySourceRecord::create(['source_key' => hash('sha256', 'inventory'), 'source_database' => 'fathersheart', 'source_table' => 'inventory', 'source_row_id' => '8',
            'checksum' => hash('sha256', 'inventory'), 'raw_payload' => ['id' => 8, 'GEN1' => 9], 'status' => 'staged']);
        $this->actingAs($admin)->get(route('admin.inventory.historical.index', ['source' => 'cane']))->assertOk()
            ->assertViewHas('records', fn ($rows) => $rows->total() === 30 && $rows->count() === 25)->assertSee('available')->assertDontSee('GEN1');
        $this->get(route('admin.inventory.historical.index', ['source' => 'inventory']))->assertOk()->assertSee('GEN1')->assertSee('9');
        $this->get(route('admin.inventory.historical.index', ['q' => '=2+3']))->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 1);
        $response = $this->get(route('admin.inventory.historical.export', ['source' => 'cane']))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=2+3", $csv);
        $this->assertStringContainsString("'12/11/2020", $csv);
        $this->assertStringNotContainsString('GEN1', $csv);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->actingAs(User::factory()->create())->get(route('admin.inventory.historical.export'))->assertForbidden();
    }

    public function test_inventory_requires_confirmed_opening_count_and_records_stock_safely(): void
    {
        $admin = $this->admin();
        $item = InventoryItem::create(['name' => 'Wheelchair', 'unit' => 'item']);
        $url = route('admin.inventory.movements.store', $item);
        $date = now()->subDay()->format('Y-m-d');
        $this->actingAs($admin)->post($url, ['type' => 'in', 'quantity' => 5, 'occurred_on' => $date])->assertSessionHasErrors('type');
        $this->actingAs($admin)->post($url, ['type' => 'opening', 'quantity' => 10, 'occurred_on' => $date])->assertSessionHasErrors('opening_confirmed');
        $this->actingAs($admin)->post($url, ['type' => 'opening', 'quantity' => 10, 'occurred_on' => $date, 'opening_confirmed' => 1])->assertRedirect();
        $this->actingAs($admin)->post($url, ['type' => 'out', 'quantity' => 11, 'occurred_on' => $date])->assertSessionHasErrors('quantity');
        $this->actingAs($admin)->post($url, ['type' => 'in', 'quantity' => 5, 'occurred_on' => $date])->assertRedirect();
        $this->actingAs($admin)->post($url, ['type' => 'out', 'quantity' => 4, 'occurred_on' => $date])->assertRedirect();
        $this->assertSame(11, (int) $item->fresh()->quantity);
        $this->assertDatabaseCount('inventory_movements', 3);
        $this->actingAs($admin)->post($url, ['type' => 'opening', 'quantity' => 0, 'occurred_on' => $date, 'opening_confirmed' => 1])->assertSessionHasErrors('type');
        $this->actingAs(User::factory()->create())->post($url, ['type' => 'out', 'quantity' => 1, 'occurred_on' => $date])->assertForbidden();
    }

    public function test_dashboard_filters_month_and_does_not_include_opening_counts_as_stock_in(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Test Recipient']);
        $person->forceFill(['created_at' => '2026-11-10 12:00:00'])->save();
        $person->assessments()->create(['status' => 'draft', 'assessed_on' => '2026-11-10']);
        $person->assessments()->create(['status' => 'complete', 'assessed_on' => '2026-11-10']);
        DistributionEvent::create(['name' => 'November mission', 'event_date' => '2026-11-20', 'status' => 'planned']);
        DistributionEvent::create(['name' => 'December mission', 'event_date' => '2026-12-20', 'status' => 'planned']);
        $item = InventoryItem::create(['name' => 'Cane', 'unit' => 'item']);
        $item->movements()->create(['type' => 'opening', 'quantity' => 10, 'occurred_on' => '2026-11-01']);
        $item->movements()->create(['type' => 'in', 'quantity' => 3, 'occurred_on' => '2026-11-02']);
        $item->movements()->create(['type' => 'out', 'quantity' => 2, 'occurred_on' => '2026-11-03']);

        $response = $this->actingAs($admin)->get(route('dashboard', ['month' => '2026-11']))->assertOk()
            ->assertSee('November mission')->assertDontSee('December mission')->assertSee('Cane');
        $response->assertViewHas('personCount', 1)->assertViewHas('ongoingCount', 1)->assertViewHas('stockIn', 1)->assertViewHas('stockOut', 1);
        $this->assertEquals(3, $response->viewData('stockByItem')->first()->received);
    }
}
