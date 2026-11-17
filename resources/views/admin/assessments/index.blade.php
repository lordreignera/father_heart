<x-admin-layout title="Assessments">
    <div class="admin-content"><div class="admin-page-heading"><div><p class="admin-eyebrow">Recipient intake</p><h1>Assessments</h1><p>Based on the current paper questionnaire. A device request is not a confirmed handover.</p></div>@can('route-admin-people-index')<a class="admin-primary-link" href="{{ route('admin.people.index') }}">Find or register person</a>@endcan</div>
        <form class="admin-filter" method="GET"><label for="assessment-status">Status</label><select id="assessment-status" name="status"><option value="">All</option>@foreach(['draft', 'assessed', 'waitlisted', 'complete', 'legacy_unverified'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select><button type="submit">Filter</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Person</th><th>Date</th><th>District</th><th>Device requested</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($assessments as $assessment)<tr><td>{{ $assessment->person->full_name }}</td><td>{{ $assessment->assessed_on?->format('d M Y') ?: '—' }}</td><td>{{ $assessment->district?->name ?: '—' }}</td><td>{{ $assessment->device_requested ?: '—' }}</td><td>{{ ucfirst(str_replace('_', ' ', $assessment->status)) }}</td><td>@can('route-admin-assessments-show')<a href="{{ route('admin.assessments.show', $assessment) }}">View</a>@endcan</td></tr>
            @empty <tr><td colspan="6">No assessments found.</td></tr> @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $assessments->links() }}</div>
    </div>
</x-admin-layout>
