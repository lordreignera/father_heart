<?php

namespace App\Actions\Fortify;

use App\Models\StaffRegistrationRequest;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class NotifyPendingStaffRegistration
{
    public function handle(Request $request, Closure $next)
    {
        $email = mb_strtolower(trim((string) $request->input(Fortify::username())));
        $pending = StaffRegistrationRequest::where('email', $email)->where('status', 'pending')->first();
        // An existing account always takes precedence; never reveal a request based on email alone.
        if ($pending && $pending->password_hash && ! User::whereRaw('LOWER(email) = ?', [$email])->exists()
            && is_string($request->input('password')) && Hash::check($request->input('password'), $pending->password_hash)) {
            throw ValidationException::withMessages([Fortify::username() => 'Your registration request is awaiting administrator approval. You can sign in after it is approved.']);
        }
        return $next($request);
    }
}
