<x-guest-layout>
    <x-slot name="pageTitle">Request staff access | Father's Heart Mobility Ministry</x-slot>
    <x-slot name="loginStyles">true</x-slot>
    <div class="fh-auth"><main class="auth-stage">
        <header class="auth-heading">
            <a class="auth-brand-mark" href="{{ route('home') }}" aria-label="Father's Heart home"><img src="{{ asset('images/fathers-heart-logo.png') }}" alt="Father's Heart Mobility Ministry"></a>
            <span class="auth-eyebrow">Father's Heart account</span>
            <h1 id="registration-title">Request staff access</h1>
            <p class="auth-intro">A super administrator must review your request before you can sign in.</p>
        </header>
        <section class="auth-shell" aria-labelledby="registration-title"><div class="auth-panel">
            @if($errors->any())<div class="auth-errors" role="alert"><strong>Please check your request.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if($roles->isEmpty())
                <p>No staff roles are currently available. Please contact your administrator.</p>
            @else
                <form class="auth-form auth-registration" method="POST" action="{{ route('staff-registration.store') }}">
                    @csrf
                    <div class="auth-field"><label for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" required autocomplete="name"></div>
                    <div class="auth-field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="191" required autocomplete="email"></div>
                    <div class="auth-field"><label for="role_id">Requested role</label><select id="role_id" name="role_id" required><option value="">Select a role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>@endforeach</select><small>Your administrator can change the role before approval.</small></div>
                    <div class="auth-field"><label for="password">Password</label><div class="auth-password-wrap"><input id="password" type="password" name="password" minlength="8" maxlength="8" required autocomplete="new-password"><button type="button" data-password-toggle aria-label="Show password" aria-controls="password" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div><small>Use exactly 8 characters.</small></div>
                    <div class="auth-field"><label for="password_confirmation">Confirm password</label><div class="auth-password-wrap"><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" maxlength="8" required autocomplete="new-password"><button type="button" data-password-toggle aria-label="Show confirmation password" aria-controls="password_confirmation" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
                    <button class="auth-submit" type="submit">Submit request</button>
                </form>
            @endif
            <p class="auth-footnote">Already have an account? <a href="{{ route('login') }}"><strong>Sign in</strong></a></p>
        </div></section>
        <a class="auth-back" href="{{ route('home') }}">&larr; Back to the ministry website</a>
    </main></div>
</x-guest-layout>
