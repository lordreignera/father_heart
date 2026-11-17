<x-admin-layout title="Intake code settings">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ route('dashboard') }}">← Dashboard</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Settings</p><h1>Intake codes</h1><p>Choose how codes are generated when a new assessment is saved.</p></div></div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ route('admin.settings.intake-code.update') }}">@csrf @method('PUT')
            <div class="admin-field-grid">
                <label>Prefix <small>(optional, letters and numbers)</small><input name="prefix" maxlength="20" value="{{ old('prefix', $settings->prefix) }}" placeholder="FH"></label>
                <label>Month and year<select name="date_format"><option value="mY" @selected(old('date_format', $settings->date_format) === 'mY')>Month then year (112026)</option><option value="Ym" @selected(old('date_format', $settings->date_format) === 'Ym')>Year then month (202611)</option></select></label>
                <label>Starting number<input type="number" name="starting_number" min="1" max="999999999" required value="{{ old('starting_number', $settings->starting_number) }}"></label>
                <label>Numbering<select name="sequence_reset"><option value="monthly" @selected(old('sequence_reset', $settings->sequence_reset) === 'monthly')>Restart each month</option><option value="continuous" @selected(old('sequence_reset', $settings->sequence_reset) === 'continuous')>Continuous sequence</option></select></label>
            </div>
            <p class="admin-help">The code includes the assessment month, a sequence number, and the person's first and last initials. Example: 112026-1000-JD. Changing these settings affects new assessments only; an existing sequence never moves backwards.</p>
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save settings</button></div>
        </form>
    </div>
</x-admin-layout>
