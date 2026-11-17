<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

class TherapyOldInput
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $encrypted = session('therapy_private_old_input');
        if (! is_string($encrypted)) return $default;
        try {
            $values = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
            return data_get($values, $key, $default);
        } catch (\Throwable) { return $default; }
    }
}
