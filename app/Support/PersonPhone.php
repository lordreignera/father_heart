<?php

namespace App\Support;

class PersonPhone
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (strlen($digits) < 7) return null;

        if (str_starts_with($digits, '00256') && strlen($digits) === 14) return '0'.substr($digits, 5);
        if (str_starts_with($digits, '256') && strlen($digits) === 12) return '0'.substr($digits, 3);

        return $digits;
    }
}
