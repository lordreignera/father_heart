<x-admin-layout :title="$person->full_name">
    <div class="admin-content"><a class="admin-back" href="{{ route('admin.people.index') }}">← People registry</a>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Person record #{{ $person->id }}</p><h1>{{ $person->full_name }}</h1><p>Phone: {{ $person->phone ?: 'Not recorded' }} @can('route-admin-assessments-index')· {{ $person->assessments->count() }} assessment(s)@endcan</p></div><div class="admin-heading-actions">@can('route-admin-people-update')<button class="admin-table-action" type="button" data-dialog-open="#person-edit-{{ $person->id }}">Edit details</button>@endcan @can('route-admin-assessments-create')<a class="admin-primary-link" href="{{ route('admin.assessments.create', ['person_id' => $person->id]) }}">New assessment</a>@endcan</div></div>
        @can('route-admin-assessments-index')
        <h2 class="admin-subheading">Assessment history</h2>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Intake code</th><th>Distribution</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($person->assessments as $assessment)<tr><td>{{ $assessment->assessed_on?->format('d M Y') ?: '—' }}</td><td>{{ $assessment->intake_code ?: '—' }}</td><td>{{ $assessment->distributionEvent?->name ?: '—' }}</td><td>{{ ucfirst(str_replace('_', ' ', $assessment->status)) }}</td><td>@can('route-admin-assessments-show')<a href="{{ route('admin.assessments.show', $assessment) }}">View</a>@endcan</td></tr>
            @empty <tr><td colspan="5">No assessments recorded yet.</td></tr> @endforelse
        </tbody></table></div>
        @endcan
        @can('route-admin-people-update')@include('admin.people._modal-form', ['person' => $person, 'modalId' => 'person-edit-'.$person->id])@endcan
    </div>
</x-admin-layout>
