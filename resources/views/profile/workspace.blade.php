@php
    $steps = [];
    if (Laravel\Fortify\Features::canUpdateProfileInformation()) $steps[] = 'Profile';
    if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords())) $steps[] = 'Password';
    if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) $steps[] = 'Two-factor';
    $steps[] = 'Sessions';
    if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures()) $steps[] = 'Account';
@endphp
<div class="admin-content admin-assessment-content profile-settings" data-assessment-form>
    <div class="admin-page-heading"><div><p class="admin-eyebrow">Account settings</p><h1>My profile</h1><p>Manage your account details, password and security. Each section saves separately.</p></div></div>
    <div class="assessment-guide" data-assessment-guide hidden>
        <div class="assessment-guide-top"><div><p class="admin-eyebrow">Account settings</p><strong data-assessment-progress aria-live="polite">Step 1 of {{ count($steps) }}</strong><p>Choose a section or view all settings.</p></div><div class="assessment-view-toggle" role="group" aria-label="Profile view"><button type="button" data-assessment-mode="steps" aria-pressed="true">Step by step</button><button type="button" data-assessment-mode="full" aria-pressed="false">Full view</button></div></div>
        <nav class="assessment-step-list" aria-label="Profile sections">@foreach($steps as $label)<button type="button" data-assessment-go="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $label }}</button>@endforeach</nav>
    </div>
    @if (Laravel\Fortify\Features::canUpdateProfileInformation())<section class="admin-form-card admin-form-section" data-assessment-step aria-label="Profile information">@livewire('profile.update-profile-information-form')</section>@endif
    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))<section class="admin-form-card admin-form-section" data-assessment-step aria-label="Update password">@livewire('profile.update-password-form')</section>@endif
    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())<section class="admin-form-card admin-form-section" data-assessment-step aria-label="Two-factor authentication">@livewire('profile.two-factor-authentication-form')</section>@endif
    <section class="admin-form-card admin-form-section" data-assessment-step aria-label="Browser sessions">@livewire('profile.logout-other-browser-sessions-form')</section>
    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())<section class="admin-form-card admin-form-section" data-assessment-step aria-label="Delete account">@livewire('profile.delete-user-form')</section>@endif
    <div class="assessment-actions"><button type="button" class="admin-secondary-button" data-assessment-prev hidden>Previous</button><span class="assessment-actions-spacer"></span><button type="button" class="admin-primary-link" data-assessment-next hidden>Next step</button></div>
</div>
