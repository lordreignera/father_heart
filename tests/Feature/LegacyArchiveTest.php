<?php

namespace Tests\Feature;

use App\Models\LegacySourceRecord;
use App\Support\LegacyArchive;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;
use Tests\TestCase;

class LegacyArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('old_rows', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('phone')->nullable();
        });
    }

    public function test_dry_run_does_not_create_archive_records(): void
    {
        DB::table('old_rows')->insert(['fullname' => 'Example', 'phone' => null]);
        $result = app(LegacyArchive::class)->run(DB::connection(), DB::connection(), ['old_rows'], 'old', false);
        $this->assertSame(1, $result['rows']);
        $this->assertDatabaseCount('legacy_source_records', 0);
        $this->assertDatabaseCount('legacy_import_batches', 0);
    }

    public function test_staging_encrypts_and_preserves_the_original_and_refuses_overwrite(): void
    {
        DB::table('old_rows')->insert(['fullname' => 'Example', 'phone' => null]);
        $archive = app(LegacyArchive::class);
        $result = $archive->run(DB::connection(), DB::connection(), ['old_rows'], 'old', true);
        $this->assertSame(1, $result['rows']);
        $record = LegacySourceRecord::firstOrFail();
        $this->assertSame('Example', $record->raw_payload['fullname']);
        $this->assertNull($record->raw_payload['phone']);
        $this->assertStringNotContainsString('Example', DB::table('legacy_source_records')->first()->raw_payload);
        $this->assertSame('staged', $record->status);
        $this->assertSame(1, json_decode(DB::table('legacy_import_batches')->value('counts'), true)['table_counts']['old_rows']);
        $this->assertDatabaseCount('people', 0);
        $this->expectException(RuntimeException::class);
        $archive->run(DB::connection(), DB::connection(), ['old_rows'], 'old', true);
    }

    public function test_failed_staging_rolls_back_the_entire_batch(): void
    {
        DB::table('old_rows')->insert([['fullname' => 'Valid', 'phone' => null], ['fullname' => "\xFF", 'phone' => null]]);
        try {
            app(LegacyArchive::class)->run(DB::connection(), DB::connection(), ['old_rows'], 'old', true);
            $this->fail('Expected invalid UTF-8 to stop staging.');
        } catch (JsonException $exception) {
            $this->assertDatabaseCount('legacy_source_records', 0);
            $this->assertDatabaseCount('legacy_import_batches', 0);
        }
    }
}
