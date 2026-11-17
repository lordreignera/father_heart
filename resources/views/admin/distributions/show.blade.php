<x-admin-layout :title="$event->name">
    <div class="admin-content">
        <a class="admin-back" href="{{ route('admin.distributions.index') }}">← Distribution events</a>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @php($oldDistricts = $historicalDistricts[\App\Support\LegacyEventLocations::normalize($event->legacy_table)] ?? [])
        <div class="admin-page-heading"><div><p class="admin-eyebrow">{{ ucfirst($event->status) }} distribution</p><h1>{{ $event->name }}</h1><p>@if($event->event_date){{ $event->event_date->format('d M Y') }}@elseif($event->legacy_period){{ $event->legacy_period }} (from old name; unverified)@else Date not set @endif · @if($event->district_id){{ $event->district?->name ?: 'Location not set' }}{{ $event->subcounty ? ' / '.$event->subcounty->name : '' }}@if($event->legacy_table) (historical; review)@endif @elseif($oldDistricts){{ implode(' / ', array_values($oldDistricts)) }} (old registry; review)@else Location not set @endif</p></div><a class="admin-primary-link" href="{{ route('admin.distributions.edit', $event) }}">Edit event</a></div>
        @if($event->notes)<p class="admin-form-card admin-preserve-lines">{{ $event->notes }}</p>@endif
        @can('route-admin-assessments-show')
        <h2 class="admin-subheading">Assessments at this event</h2>
        <p class="admin-help">Historical device and recorded date are copied from the old record for review. The old date may be the last edit date, and neither value confirms a device was delivered.</p>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Person</th><th>Assessment date</th><th>Device considered</th><th>Historical device (unverified)</th><th>Historical recorded date (unverified)</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($assessments as $assessment)
                    @php($historical = $assessment->legacySource?->raw_payload ?? [])
                    @php($oldDevice = $historical['givemobility'] ?? null)
                    @php($oldDate = $historical['date'] ?? null)
                <tr><td data-label="Person">{{ $assessment->person->full_name }}</td><td data-label="Assessment date">{{ $assessment->assessed_on?->format('d M Y') ?: '—' }}</td><td data-label="Device considered">{{ $assessment->device_requested ?: '—' }}</td>
                    <td data-label="Historical device (unverified)">{{ is_scalar($oldDevice) && trim((string) $oldDevice) !== '' ? $oldDevice : '—' }}</td><td data-label="Historical recorded date (unverified)">{{ is_scalar($oldDate) && trim((string) $oldDate) !== '' ? $oldDate : '—' }}</td>
                    <td data-label="Status">{{ ucfirst(str_replace('_', ' ', $assessment->status)) }}</td><td><a href="{{ route('admin.assessments.show', $assessment) }}">View</a></td></tr>
            @empty <tr><td colspan="7">No assessments linked yet. Register a person, then select this event on their assessment.</td></tr> @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $assessments->links() }}</div>
        @endcan
    </div>
</x-admin-layout>
