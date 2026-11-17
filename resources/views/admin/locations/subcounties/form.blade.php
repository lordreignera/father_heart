<x-admin-layout :title="$subcounty->exists ? 'Edit local unit' : 'Add local unit'">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ route('admin.subcounties.index') }}">← Subcounties & local units</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Locations</p><h1>{{ $subcounty->exists ? 'Edit local unit' : 'Add local unit' }}</h1></div></div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $subcounty->exists ? route('admin.subcounties.update', $subcounty) : route('admin.subcounties.store') }}">
            @csrf @if($subcounty->exists) @method('PUT') @endif
            <div class="admin-field-grid">
                <label>District or city<select name="district_id" required><option value="">Select a district</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected(old('district_id', $subcounty->district_id ?? request('district_id')) == $district->id)>{{ $district->name }}{{ $district->kind === 'city' ? ' City' : '' }}</option>@endforeach</select></label>
                <label>Name<input name="name" value="{{ old('name', $subcounty->name) }}" required maxlength="120"></label>
                <label>Type<select name="kind" required>@foreach(['subcounty' => 'Subcounty', 'town_council' => 'Town council', 'division' => 'Division'] as $value => $label)<option value="{{ $value }}" @selected(old('kind', $subcounty->kind ?: 'subcounty') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label>UBOS code <small>(optional)</small><input name="ubos_code" value="{{ old('ubos_code', $subcounty->ubos_code) }}" maxlength="16"></label>
                <label>Status<select name="is_active"><option value="1" @selected(old('is_active', $subcounty->is_active ?? true) == 1)>Active</option><option value="0" @selected(old('is_active', $subcounty->is_active ?? true) == 0)>Inactive</option></select></label>
            </div>
            @if($subcounty->source_id) <p class="admin-help">Imported location key: {{ $subcounty->source_id }}.</p> @endif
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save local unit</button></div>
        </form>
        @if($subcounty->exists)<form method="POST" action="{{ route('admin.subcounties.destroy', $subcounty) }}" onsubmit="return confirm('Archive this local unit? Existing linked records will be preserved.');">@csrf @method('DELETE')<button class="admin-text-danger" type="submit">Archive local unit</button></form>@endif
    </div>
</x-admin-layout>
