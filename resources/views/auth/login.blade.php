<x-guest-layout>
    <x-slot name="pageTitle">Staff login | Father's Heart Mobility Ministry</x-slot>
    <x-slot name="loginStyles">true</x-slot>

    <div class="fh-auth">
        <main class="auth-stage">
            <header class="auth-heading">
                <a class="auth-brand-mark" href="{{ route('home') }}" aria-label="Father's Heart home">
                    <img src="{{ asset('images/fathers-heart-logo.png') }}" alt="Father's Heart Mobility Ministry">
                </a>
                <span class="auth-eyebrow">Father's Heart account</span>
                <h1 id="login-title">Welcome back</h1>
                <p class="auth-intro">Sign in to continue to your ministry workspace.</p>
            </header>

            <section class="auth-shell" aria-labelledby="login-title">
                <div class="auth-panel">

                    @session('status')
                        <div class="auth-status" role="status">{{ $value }}</div>
                    @endsession

                    @if ($errors->any())
                        <div class="auth-errors" role="alert">
                            <strong>We couldn't sign you in.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form class="auth-form" method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="auth-field">
                            <label for="email">Email address</label>
                            <div class="auth-input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4.5 7 7.5 6 7.5-6"/></svg>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="username" @error('email') aria-invalid="true" @enderror>
                            </div>
                        </div>

                        <div class="auth-field">
                            <div class="auth-field-heading">
                                <label for="password">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">Forgot password?</a>
                                @endif
                            </div>
                            <div class="auth-input-wrap auth-password-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                <input id="password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                                <button type="button" data-password-toggle aria-label="Show password" aria-controls="password" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                            </div>
                        </div>

                        <label class="auth-remember" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember">
                            <span>Keep me signed in</span>
                        </label>

                        <button class="auth-submit" type="submit">Sign in</button>
                    </form>

                    <p class="auth-footnote">Need an account? <a href="{{ route('staff-registration.create') }}"><strong>Request staff registration</strong></a><br>Need help signing in? Contact your administrator.</p>
                </div>
            </section>
            <a class="auth-back" href="{{ route('home') }}">← Back to the ministry website</a>
        </main>
    </div>
</x-guest-layout>
