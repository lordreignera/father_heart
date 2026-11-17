<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class TherapyCatalogues
{
    public const ITEMS = [
        'disabilities' => ['table' => 'therapy_disabilities', 'label' => 'Disabilities / diagnoses'],
        'mobility' => ['table' => 'therapy_mobility_options', 'label' => 'Mobility'],
        'communication' => ['table' => 'therapy_communication_options', 'label' => 'Communication'],
        'previous_therapy' => ['table' => 'therapy_previous_therapies', 'label' => 'Previous therapies'],
        'musical_interests' => ['table' => 'therapy_musical_interests', 'label' => 'Musical interests'],
        'session_type' => ['table' => 'therapy_session_types', 'label' => 'Session types'],
        'frequency' => ['table' => 'therapy_frequencies', 'label' => 'Frequencies'],
    ];

    public static function table(string $key): string
    {
        abort_unless(isset(self::ITEMS[$key]), 404);
        return self::ITEMS[$key]['table'];
    }

    public static function active(string $key)
    {
        return DB::table(self::table($key))->where('is_active', true)->orderBy('name')->get();
    }

    public static function activeGrouped()
    {
        return collect(self::ITEMS)->map(fn ($definition, $key) => self::active($key));
    }
}
