<?php

namespace Tests\Feature;

use App\Models\LegacySourceRecord;
use App\Models\User;
use App\Support\LegacyRecipientMapper;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyRecipientMapperTest extends TestCase
{
    use RefreshDatabase;

    private function original(array $extra = []): LegacySourceRecord
    {
        $payload = $extra + ['id' => 14, 'fullname' => 'Example Person', 'phone' => '', 'givemobility' => 'Chair', 'date' => '02/03/2020', 'disabilitystory' => '<script>bad</script>'];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $id = DB::table('legacy_source_records')->insertGetId(['source_key' => hash('sha256', 'example'), 'source_database' => 'old', 'source_table' => 'mission', 'source_row_id' => '14', 'raw_payload' => Crypt::encryptString($json), 'checksum' => hash('sha256', $json), 'status' => 'staged']);
        return LegacySourceRecord::findOrFail($id);
    }

    public function test_mapping_is_conservative_and_resumable(): void
    {
        $record = $this->original();
        $mapper = app(LegacyRecipientMapper::class);
        $this->assertSame(1, $mapper->map(DB::connection(), 'mission', true, false)['new']);
        $this->assertDatabaseCount('people', 0);
        $this->assertSame(1, $mapper->map(DB::connection(), 'mission', true, true)['new']);
        $this->assertDatabaseCount('people', 1);
        $this->assertDatabaseCount('assessments', 1);
        $assessment = DB::table('assessments')->first();
        $this->assertNull($assessment->assessed_on);
        $this->assertNull($assessment->device_requested);
        $this->assertSame('legacy_unverified', $assessment->status);
        $this->assertSame('assessment', $record->fresh()->mapped_type);
        $this->assertSame(1, $mapper->map(DB::connection(), 'mission', true, true)['existing']);
        $this->assertDatabaseCount('people', 1);
    }

    public function test_original_answers_are_protected_and_html_escaped(): void
    {
        $this->original();
        app(LegacyRecipientMapper::class)->map(DB::connection(), 'mission', true, true);
        $assessment = DB::table('assessments')->first();
        $this->get(route('admin.assessments.show', $assessment->id))->assertRedirect('/login');
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $this->actingAs($admin)->get(route('admin.assessments.show', $assessment->id))
            ->assertOk()->assertSee('Original historical record')->assertSee('&lt;script&gt;bad&lt;/script&gt;', false)->assertDontSee('<script>bad</script>', false);
    }

    public function test_historical_records_do_not_appear_as_new_registrations(): void
    {
        $this->original();
        app(LegacyRecipientMapper::class)->map(DB::connection(), 'mission', true, true);
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $this->assertSame(0, $response->viewData('personCount'));
        $this->assertSame(0, $response->viewData('ongoingCount'));
        $this->actingAs($admin)->get('/admin/people')->assertOk()->assertSee('Historical — date unknown');
        $this->actingAs($admin)->get('/admin/people?date_from='.now()->format('Y-m-d'))->assertDontSee('Example Person');
    }

    public function test_historical_photo_route_is_private_and_cannot_escape_the_archive(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('legacy/good.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZ8AAAAASUVORK5CYII='));
        Storage::disk('local')->put('outside.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZ8AAAAASUVORK5CYII='));
        config(['legacy.media_root' => Storage::disk('local')->path('legacy'), 'legacy.media_ready' => true]);
        $this->original(['afterr' => 'images/good.png', 'beforee' => 'images/../outside.png']);
        app(LegacyRecipientMapper::class)->map(DB::connection(), 'mission', true, true);
        $id = DB::table('assessments')->value('id');
        $this->get(route('admin.assessments.legacy-photo', [$id, 'after']))->assertRedirect('/login');
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $this->actingAs($admin)->get(route('admin.assessments.legacy-photo', [$id, 'after']))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($admin)->get(route('admin.assessments.show', $id))->assertOk()
            ->assertSee('Historical match, unverified')->assertSee(route('admin.assessments.legacy-photo', [$id, 'after']), false)
            ->assertDontSee(route('admin.assessments.legacy-photo', [$id, 'before']), false);
        $this->actingAs($admin)->get(route('admin.assessments.legacy-photo', [$id, 'before']))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.assessments.legacy-photo', [$id, 'after']))->assertForbidden();
        config(['legacy.media_ready' => false]);
        $this->actingAs($admin)->get(route('admin.assessments.legacy-photo', [$id, 'after']))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.assessments.show', $id))->assertOk()->assertDontSee(route('admin.assessments.legacy-photo', [$id, 'after']), false);
    }

    public function test_team_history_does_not_expose_password_hashes(): void
    {
        LegacySourceRecord::create(['source_key' => hash('sha256', 'team'), 'source_database' => 'fathersheart', 'source_table' => 'staff', 'source_row_id' => '1', 'checksum' => hash('sha256', 'team'), 'raw_payload' => ['id' => 1, 'username' => 'old-user', 'password' => 'secret-old-hash'], 'status' => 'staged']);
        $this->get(route('admin.historical.index', 'team'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('admin.historical.index', 'team'))->assertForbidden();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        $this->actingAs($admin)->get(route('admin.historical.index', 'team'))->assertOk()->assertSee('old-user')->assertDontSee('secret-old-hash');
    }
}
