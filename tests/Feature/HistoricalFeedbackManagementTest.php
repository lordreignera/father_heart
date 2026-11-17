<?php

namespace Tests\Feature;

use App\Models\LegacySourceRecord;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricalFeedbackManagementTest extends TestCase
{
    use RefreshDatabase;

    private function record(string $table = 'ratings'): LegacySourceRecord
    {
        return LegacySourceRecord::create(['source_key' => hash('sha256', $table), 'source_database' => 'fathersheart', 'source_table' => $table,
            'source_row_id' => '42', 'checksum' => hash('sha256', 'original'), 'raw_payload' => ['id' => 42, 'staff' => 'old-staff', 'recipient' => 'old-recipient', 'rating' => 'Good', 'secret' => 'not-visible'], 'status' => 'staged']);
    }

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        return User::factory()->create()->assignRole('Super Administrator');
    }

    public function test_feedback_table_and_modals_require_both_permissions_and_hide_extra_source_fields(): void
    {
        $record = $this->record();
        $admin = $this->admin();
        $this->get('/admin/historical/feedback')->assertRedirect('/login');
        $staff = User::factory()->create()->givePermissionTo(['access-admin']);
        $this->actingAs($staff)->get('/admin/historical/feedback')->assertForbidden();
        $staff->givePermissionTo(['route-admin-historical-feedback-index', 'route-admin-historical-feedback-update', 'route-admin-historical-feedback-archive']);
        $this->actingAs($staff)->get('/admin/historical/feedback')->assertOk()->assertSee('old-staff')->assertSee('old-recipient')->assertSee('feedback-view-'.$record->id)->assertDontSee('feedback-edit-'.$record->id)->assertDontSee('not-visible');
        $this->actingAs($admin)->get('/admin/historical/feedback')->assertOk()->assertSee('feedback-edit-'.$record->id)->assertSee('feedback-delete-'.$record->id);
        $this->actingAs($staff)->put(route('admin.historical.feedback.update', $record), [])->assertForbidden();
        $this->delete(route('admin.historical.feedback.archive', $record))->assertForbidden();
    }

    public function test_edits_are_encrypted_and_original_is_preserved_then_archive_and_restore(): void
    {
        $record = $this->record();
        $this->actingAs($this->admin())->put(route('admin.historical.feedback.update', $record), ['staff' => 'new-staff', 'recipient' => '', 'rating' => 'Updated'])->assertRedirect();
        $this->assertSame('old-staff', $record->fresh()->raw_payload['staff']);
        $this->assertStringNotContainsString('new-staff', DB::table('historical_feedback_profiles')->value('staff'));
        $this->get('/admin/historical/feedback')->assertSee('new-staff')->assertDontSee('old-recipient');
        $this->delete(route('admin.historical.feedback.archive', $record))->assertRedirect();
        $this->get('/admin/historical/feedback')->assertDontSee('new-staff');
        $this->get('/admin/historical/feedback?archived=1')->assertSee('new-staff')->assertSee('Restore');
        $this->put(route('admin.historical.feedback.update', $record), ['staff' => 'other'])->assertSessionHasErrors('record');
        $this->post(route('admin.historical.feedback.restore', $record))->assertRedirect();
        $this->get('/admin/historical/feedback')->assertSee('new-staff');
        $this->assertDatabaseHas('legacy_source_records', ['id' => $record->id]);
    }

    public function test_other_historical_record_types_cannot_be_modified(): void
    {
        $record = $this->record('staff');
        $this->actingAs($this->admin())->delete(route('admin.historical.feedback.archive', $record))->assertNotFound();
        $this->assertDatabaseCount('historical_feedback_profiles', 0);
    }

    public function test_filter_and_excel_compatible_export_cover_all_matching_pages_and_protect_formulas(): void
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 30; $i++) {
            LegacySourceRecord::create(['source_key' => hash('sha256', 'feedback-'.$i), 'source_database' => 'fathersheart', 'source_table' => 'ratings',
                'source_row_id' => (string) $i, 'checksum' => hash('sha256', 'original-'.$i), 'raw_payload' => ['staff' => 'Care Team', 'recipient' => 'Recipient '.$i, 'rating' => $i === 30 ? '=2+3' : 'Positive'], 'status' => 'staged']);
        }
        $this->actingAs($admin)->get(route('admin.historical.feedback.index', ['q' => 'care team']))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 30 && $records->count() === 25);
        $this->get(route('admin.historical.feedback.index', ['q' => 'recipient 30']))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1);
        $response = $this->get(route('admin.historical.feedback.export', ['q' => 'care team']))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Recipient 30', $csv);
        $this->assertStringContainsString("'=2+3", $csv);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $filteredCsv = $this->get(route('admin.historical.feedback.export', ['q' => 'recipient 30']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Recipient 30', $filteredCsv);
        $this->assertStringNotContainsString('Recipient 29', $filteredCsv);
        $staff = User::factory()->create()->givePermissionTo(['access-admin', 'manage-staff']);
        $this->actingAs($staff)->get(route('admin.historical.feedback.export'))->assertForbidden();
    }
}
