<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_filters_and_exports_all_matching_movements(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('Super Administrator');
        $item = InventoryItem::create(['name' => 'Cane', 'unit' => 'item']);
        for ($i = 1; $i <= 30; $i++) {
            $item->movements()->create(['type' => 'in', 'quantity' => $i, 'occurred_on' => '2026-11-10', 'reference' => $i === 30 ? '=unsafe' : 'Batch', 'recorded_by' => $admin->id]);
        }
        $item->movements()->create(['type' => 'opening', 'quantity' => 1, 'occurred_on' => '2026-11-01']);
        $this->actingAs($admin)->get(route('admin.inventory.movements.index', ['type' => 'in', 'q' => 'cane']))->assertOk()
            ->assertViewHas('movements', fn ($rows) => $rows->total() === 30 && $rows->count() === 25);
        $csv = $this->get(route('admin.inventory.movements.export', ['type' => 'in']))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=unsafe", $csv);
        $this->assertStringContainsString("'2026-11-10", $csv);
        $this->assertStringNotContainsString('opening', $csv);
        $this->get(route('admin.inventory.movements.index', ['date_from' => '2026-11-11', 'date_to' => '2026-11-01']))->assertSessionHasErrors('date_to');
    }

    public function test_view_and_export_permissions_are_independent(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-inventory-movements-index']);
        $this->actingAs($user)->get(route('admin.inventory.movements.index'))->assertOk()->assertDontSee('Export all matching');
        $this->get(route('admin.inventory.movements.export'))->assertForbidden();
        $user->givePermissionTo('route-admin-inventory-movements-export');
        $this->get(route('admin.inventory.movements.export'))->assertOk();
    }
}
