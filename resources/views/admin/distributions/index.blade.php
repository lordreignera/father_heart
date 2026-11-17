<x-admin-layout title="Distribution events">
    <div class="admin-content">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Mobility missions</p><h1>Distribution events</h1><p>Plan missions and keep their linked assessments organized.</p></div>
            <div>@can('route-admin-historical-events')<a class="admin-table-action" href="{{ route('admin.historical.index', 'events') }}">Historical event registry</a>@endcan @can('route-admin-distributions-store')<button class="admin-primary-link" type="button" data-dialog-open="#event-create-dialog">Plan event</button>@endcan</div>
        </div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->has('event')) <p class="admin-error" role="alert">{{ $errors->first('event') }}</p> @endif
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Assessments</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($events as $event)
                @php($oldDistricts = $historicalDistricts[\App\Support\LegacyEventLocations::normalize($event->legacy_table)] ?? [])
                <tr><td><strong>{{ $event->name }}</strong></td><td>@if($event->event_date){{ $event->event_date->format('d M Y') }}@elseif($event->legacy_period){{ $event->legacy_period }} <small>(old name; unverified)</small>@else — @endif</td><td>@if($event->district_id){{ $event->district?->name ?: '—' }}{{ $event->subcounty ? ' / '.$event->subcounty->name : '' }}@if($event->legacy_table) <small>(historical; review)</small>@endif @elseif($oldDistricts){{ implode(' / ', array_values($oldDistricts)) }} <small>(old registry; review)</small>@else — @endif</td><td>{{ $event->assessments_count }}</td><td>{{ ucfirst($event->status) }}</td><td><div class="admin-person-actions"><button class="admin-person-action admin-person-action-view" type="button" data-dialog-open="#event-view-{{ $event->id }}">View</button>@can('route-admin-distributions-update')<button class="admin-person-action admin-person-action-edit" type="button" data-dialog-open="#event-edit-{{ $event->id }}">Edit</button>@endcan @can('route-admin-distributions-destroy')<button class="admin-person-action admin-person-action-delete" type="button" data-dialog-open="#event-delete-{{ $event->id }}">Delete</button>@endcan</div></td></tr>
            @empty
                <tr><td colspan="6">No distribution events yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="admin-pagination">{{ $events->links() }}</div>
        @can('route-admin-distributions-store')@include('admin.distributions._modal-form', ['event' => $newEvent, 'districts' => $districts, 'modalId' => 'event-create-dialog', 'creating' => true])@endcan
        @foreach($events as $event)
            @can('route-admin-distributions-update')@include('admin.distributions._modal-form', ['event' => $event, 'districts' => $districts, 'modalId' => 'event-edit-'.$event->id])@endcan
            @include('admin.distributions._record-modals', ['event' => $event])
        @endforeach
    </div>
</x-admin-layout>
