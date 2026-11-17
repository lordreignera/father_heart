<x-admin-layout :title="$district->exists ? 'Edit district' : 'Add district'">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ route('admin.districts.index') }}">← Districts & cities</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Locations</p><h1>{{ $district->exists ? 'Edit district or city' : 'Add district or city' }}</h1></div></div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $district->exists ? route('admin.districts.update', $district) : route('admin.districts.store') }}">
            @csrf @if($district->exists) @method('PUT') @endif
            <div class="admin-field-grid">
                <label>Name<input name="name" value="{{ old('name', $district->name) }}" required maxlength="120"></label>
                <label>Type<select name="kind" required><option value="district" @selected(old('kind', $district->kind ?: 'district') === 'district')>District</option><option value="city" @selected(old('kind', $district->kind) === 'city')>City</option></select></label>
                <label>UBOS code <small>(optional)</small><input name="ubos_code" value="{{ old('ubos_code', $district->ubos_code) }}" maxlength="16"></label>
                <label>Status<select name="is_active"><option value="1" @selected(old('is_active', $district->is_active ?? true) == 1)>Active</option><option value="0" @selected(old('is_active', $district->is_active ?? true) == 0)>Inactive</option></select></label>
            </div>
            @if($district->source_id) <p class="admin-help">Imported location key: {{ $district->source_id }}. This key is preserved for future data reconciliation.</p> @endif
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save district</button></div>
        </form>
        @if($district->exists)
            <form method="POST" action="{{ route('admin.districts.destroy', $district) }}" onsubmit="return confirm('Archive this district? Existing linked records will be preserved.');">
                @csrf @method('DELETE')<button class="admin-text-danger" type="submit">Archive district</button>
            </form>
        @endif
    </div>
</x-admin-layout>
