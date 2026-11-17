@props(['event', 'districts', 'modalId', 'creating' => false])
@php($returning = old('_modal') === $modalId)
<dialog class="admin-dialog" id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" @if($returning) open @endif>
    <div class="admin-dialog-head"><div><p class="admin-eyebrow">Mobility missions</p><h2 id="{{ $modalId }}-title">{{ $creating ? 'Plan a distribution event' : 'Edit distribution event' }}</h2><p>Organize the event and link assessments to it.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
    @if($returning && $errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <form method="POST" action="{{ $creating ? route('admin.distributions.store') : route('admin.distributions.update', $event) }}">
        @csrf @unless($creating) @method('PUT') @endunless
        <input type="hidden" name="_modal" value="{{ $modalId }}">
        <div class="admin-field-grid">
            <label>Event name<input name="name" value="{{ $returning ? old('name') : $event->name }}" required maxlength="180"></label>
            <label>Event date<input type="date" name="event_date" value="{{ $returning ? old('event_date') : $event->event_date?->format('Y-m-d') }}"></label>
            <label>Search districts<input type="search" data-district-search="{{ $modalId }}-district" placeholder="Type a district name" autocomplete="off"></label>
            <label>District or city<select id="{{ $modalId }}-district" name="district_id" data-location-district="{{ $modalId }}-subcounty" data-subcounty-url="{{ route('admin.location-options.subcounties') }}"><option value="">Not selected</option>@foreach($districts as $district) @if($district->is_active || $district->id == $event->district_id)<option value="{{ $district->id }}" @selected(($returning ? old('district_id') : $event->district_id) == $district->id)>{{ $district->name }}</option>@endif @endforeach</select></label>
            <label>Subcounty / town council / division<select id="{{ $modalId }}-subcounty" name="subcounty_id" data-selected="{{ $returning ? old('subcounty_id') : $event->subcounty_id }}"><option value="">Select district first</option>@if($returning ? old('subcounty_id') : $event->subcounty_id)<option value="{{ $returning ? old('subcounty_id') : $event->subcounty_id }}" selected>Previously selected local unit</option>@endif</select></label>
            <label>Status<select name="status" required>@foreach(['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'historical' => 'Historical — date unverified'] as $value => $label)<option value="{{ $value }}" @selected(($returning ? old('status') : ($event->status ?: 'planned')) === $value)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <label class="admin-wide-label">Notes<textarea name="notes" rows="3">{{ $returning ? old('notes') : $event->notes }}</textarea></label>
        <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">{{ $creating ? 'Create event' : 'Save changes' }}</button></div>
    </form>
</dialog>
