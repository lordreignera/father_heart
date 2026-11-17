<?php

namespace App\Support;

class LegacyEventPeriod
{
    public static function describe(?string $table): ?string
    {
        if (! $table) return null;
        $table = preg_replace('/__case_variant$/i', '', strtolower($table));
        $tokens = array_values(array_filter(explode('_', $table), fn ($part) => $part !== ''));
        $years = array_values(array_unique(array_filter($tokens, fn ($part) => preg_match('/^(19|20)\d{2}$/', $part))));
        if (count($years) !== 1) return null;
        $year = (int) $years[0];
        $monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        $aliases = ['jan' => 1, 'january' => 1, 'feb' => 2, 'february' => 2, 'febuary' => 2, 'mar' => 3, 'march' => 3, 'apr' => 4, 'april' => 4, 'may' => 5, 'jun' => 6, 'june' => 6, 'jul' => 7, 'july' => 7, 'aug' => 8, 'august' => 8, 'sep' => 9, 'sept' => 9, 'september' => 9, 'oct' => 10, 'october' => 10, '0ctober' => 10, 'nov' => 11, 'november' => 11, 'dec' => 12, 'december' => 12, 'decemeber' => 12];
        $months = [];
        $monthPosition = null;
        foreach ($tokens as $index => $token) {
            if (isset($aliases[$token])) { $months[$aliases[$token]] = $monthNames[$aliases[$token]]; $monthPosition = $index; }
        }
        if (! $months) return (string) $year;
        if (count($months) === 1 && $monthPosition > 0 && preg_match('/^(\d{1,2})(?:st|nd|rd|th)?$/', $tokens[$monthPosition - 1], $match)) {
            $day = (int) $match[1];
            $month = array_key_first($months);
            if (checkdate($month, $day, $year)) return $day.' '.$monthNames[$month].' '.$year;
        }
        return implode(' / ', array_values($months)).' '.$year;
    }
}
