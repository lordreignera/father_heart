<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsOnlyNewDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TherapyMusicOptionsSeeder extends Seeder
{
    use SeedsOnlyNewDatabase;

    public function run(): void
    {
        $this->assertNewDatabase();
        $defaults = [
            'therapy_previous_therapies' => ['Music therapy', 'Occupational therapy', 'Speech therapy', 'None'],
            'therapy_musical_interests' => ['Singing', 'Drums / percussion', 'Piano / keyboard', 'Guitar', 'Listening to music', 'Dancing / movement', 'Flute / wind instruments', 'Other'],
            'therapy_session_types' => ['Individual (1-on-1)', 'Small group (2–4)', 'Large group (5+)', 'No preference'],
            'therapy_frequencies' => ['Once a week', 'Twice a week', 'Three times a week', 'As recommended'],
        ];
        foreach ($defaults as $table => $names) foreach ($names as $name) {
            DB::table($table)->insertOrIgnore(['name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
