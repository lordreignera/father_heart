<?php

namespace App\Support;

use App\Models\StaffRegistrationRequest;
use Closure;

class PendingRegistrationEmail
{
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && StaffRegistrationRequest::where('status', 'pending')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($value))])->exists()) {
                $fail('This email has a pending staff registration request. Review that request instead.');
            }
        };
    }
}
