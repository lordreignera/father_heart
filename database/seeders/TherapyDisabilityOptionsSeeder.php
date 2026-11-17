<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TherapyDisabilityOptionsSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $this->assertNewDatabase();
        foreach (['Autism spectrum', 'Down syndrome', 'Cerebral palsy', 'ADHD', 'Hearing impairment', 'Visual impairment', 'Speech / language delay', 'Other'] as $name) {
            DB::table('therapy_disabilities')->insertOrIgnore([
                'name' => $name, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
