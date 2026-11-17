<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntakeCodeGenerator
{
    // Call inside the transaction that creates the assessment.
    public function generate(Person $person, ?string $assessedOn): string
    {
        $settings = DB::table('intake_code_settings')->where('id', 1)->lockForUpdate()->first();
        if (! $settings) throw new \RuntimeException('Intake code settings are missing.');
        $date = $assessedOn ? Carbon::parse($assessedOn) : now();
        $period = $settings->sequence_reset === 'monthly' ? 'm:'.$date->format('Ym') : 'continuous';
        $sequence = DB::table('intake_code_sequences')->where('period', $period)->first();
        $number = max((int) $settings->starting_number, $sequence ? (int) $sequence->last_number + 1 : 0);
        $words = preg_split('/\s+/', trim(Str::ascii($person->full_name)), -1, PREG_SPLIT_NO_EMPTY);
        $initials = strtoupper(substr($words[0] ?? '', 0, 1).(count($words) > 1 ? substr(end($words), 0, 1) : ''));
        $initials = preg_replace('/[^A-Z0-9]/', '', $initials) ?: 'X';

        do {
            $parts = array_filter([$settings->prefix, $date->format($settings->date_format), (string) $number, $initials], fn ($part) => $part !== '');
            $code = implode('-', $parts);
            $number++;
        } while (Assessment::where('intake_code', $code)->exists());

        DB::table('intake_code_sequences')->updateOrInsert(['period' => $period], [
            'last_number' => $number - 1, 'updated_at' => now(), 'created_at' => $sequence->created_at ?? now(),
        ]);
        return $code;
    }
}
