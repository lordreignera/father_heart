@props(['district', 'modalId', 'creating' => false])
@php($returning = old('_modal') === $modalId)
<dialog class="admin-dialog" id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" @if($returning) open @endif>
    <div class="admin-dialog-head"><div><p class="admin-eyebrow">Locations</p><h2 id="{{ $modalId }}-title">{{ $creating ? 'Add district or city' : 'Edit district or city' }}</h2><p>Maintain the district and city list used by intake records.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
    @if($returning && $errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <form class="admin-dialog-form" method="POST" action="{{ $creating ? route('admin.districts.store') : route('admin.districts.update', $district) }}">
        @csrf @unless($creating) @method('PUT') @endunless
        <input type="hidden" name="_modal" value="{{ $modalId }}">
        <div class="admin-field-grid"><label>Name<input name="name" value="{{ $returning ? old('name') : $district->name }}" required maxlength="120"></label><label>Type<select name="kind" required><option value="district" @selected(($returning ? old('kind') : ($district->kind ?: 'district')) === 'district')>District</option><option value="city" @selected(($returning ? old('kind') : $district->kind) === 'city')>City</option></select></label><label>UBOS code <small>(optional)</small><input name="ubos_code" value="{{ $returning ? old('ubos_code') : $district->ubos_code }}" maxlength="16"></label><label>Status<select name="is_active"><option value="1" @selected(($returning ? old('is_active', '1') : (int) ($district->is_active ?? true)) == 1)>Active</option><option value="0" @selected(($returning ? old('is_active') : (int) ($district->is_active ?? true)) == 0)>Inactive</option></select></label></div>
        @if($district->source_id)<p class="admin-help">Imported location key: {{ $district->source_id }}. This key is preserved for reconciliation.</p>@endif
        <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">{{ $creating ? 'Add district' : 'Save changes' }}</button></div>
    </form>
</dialog>
