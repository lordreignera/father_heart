<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

class PersonPhoneGuard
{
    public static function synchronized(?string $phone, Closure $operation): mixed
    {
        $number = PersonPhone::normalize($phone);
        if ($number === null) return $operation();
        $key = hash_hmac('sha256', $number, config('app.key'));

        return DB::transaction(function () use ($key, $operation) {
            DB::table('person_phone_locks')->insertOrIgnore(['number_key' => $key, 'created_at' => now()]);
            DB::table('person_phone_locks')->where('number_key', $key)->lockForUpdate()->firstOrFail();
            return $operation();
        }, 3);
    }
}
