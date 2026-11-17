<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Subcounty;
use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UgandaLocationsSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $this->assertNewDatabase();
        $locations = json_decode(file_get_contents(__DIR__.'/data/uganda_locations.json'), true, flags: JSON_THROW_ON_ERROR);
        if (count($locations['districts']) !== 146 || count($locations['subcounties']) !== 2191) {
            throw new RuntimeException('Uganda location fixture is incomplete.');
        }

        DB::transaction(function () use ($locations): void {
            foreach ($locations['districts'] as $row) {
                District::firstOrCreate(['source_id' => $row['source_id']], [
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                    'ubos_code' => $row['ubos_code'],
                ]);
            }

            $districtIds = District::whereNotNull('source_id')->pluck('id', 'source_id');
            foreach (array_chunk($locations['subcounties'], 250) as $chunk) {
                foreach ($chunk as $row) {
                    $districtId = $districtIds[$row['district_source_id']] ?? null;
                    if (! $districtId) {
                        throw new RuntimeException('Missing district for '.$row['source_id']);
                    }
                    Subcounty::firstOrCreate(['source_id' => $row['source_id']], [
                        'district_id' => $districtId,
                        'name' => $row['name'],
                        'kind' => $row['kind'],
                        'ubos_code' => $row['ubos_code'],
                    ]);
                }
            }
        });
    }
}
