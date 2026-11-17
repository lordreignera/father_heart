<x-admin-layout :title="$event->exists ? 'Edit distribution event' : 'Plan distribution event'">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ $event->exists ? route('admin.distributions.show', $event) : route('admin.distributions.index') }}">← Distribution events</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Mobility missions</p><h1>{{ $event->exists ? 'Edit distribution event' : 'Plan a distribution event' }}</h1><p>Link people’s assessments to this event after registration.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $event->exists ? route('admin.distributions.update', $event) : route('admin.distributions.store') }}">
            @csrf @if($event->exists) @method('PUT') @endif
            <div class="admin-field-grid">
                <label>Event name<input name="name" value="{{ old('name', $event->name) }}" required maxlength="180"></label>
                <label>Event date<input type="date" name="event_date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}"></label>
                <label>Search districts<input type="search" data-district-search="event-district" placeholder="Type a district name" autocomplete="off"></label>
                <label>District or city<select id="event-district" name="district_id" data-location-district="event-subcounty" data-subcounty-url="{{ route('admin.location-options.subcounties') }}"><option value="">Not selected</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected(old('district_id', $event->district_id) == $district->id)>{{ $district->name }}</option>@endforeach</select></label>
                <label>Subcounty / town council / division<select id="event-subcounty" name="subcounty_id" data-selected="{{ old('subcounty_id', $event->subcounty_id) }}"><option value="">Select district first</option>@if(old('subcounty_id', $event->subcounty_id))<option value="{{ old('subcounty_id', $event->subcounty_id) }}" selected>Previously selected local unit</option>@endif</select></label>
                <label>Status<select name="status" required>@foreach(['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'historical' => 'Historical — date unverified'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $event->status ?: 'planned') === $value)>{{ $label }}</option>@endforeach</select></label>
            </div>
            <label class="admin-wide-label">Notes<textarea name="notes" rows="3">{{ old('notes', $event->notes) }}</textarea></label>
            @if($event->legacy_table)<p class="admin-help">Legacy source table: {{ $event->legacy_table }}. This identity is not editable here.</p>@endif
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save event</button></div>
        </form>
    </div>
</x-admin-layout>
