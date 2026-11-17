<x-admin-layout title="People registry">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Care records</p><h1>People registry</h1><p>Historical entries may describe the same person. Records are never merged by name or phone automatically. Registration-date filters exclude historical entries whose original date is unknown.</p></div>@can('route-admin-people-store')<button class="admin-primary-link" type="button" data-dialog-open="#person-create-dialog">Register person</button>@endcan</div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->has('person')) <p class="admin-error" role="alert">{{ $errors->first('person') }}</p> @endif
        @if($errors->hasAny(['q', 'date_from', 'date_to'])) <div class="admin-error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="GET" action="{{ route('admin.people.index') }}" class="admin-filter admin-people-filter">
            <label for="people-search">Name, phone or person number<input id="people-search" type="search" name="q" value="{{ request('q') }}" maxlength="180" placeholder="Search people"></label>
            <label for="people-date-from">Registered from<input id="people-date-from" type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label for="people-date-to">Registered to<input id="people-date-to" type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <button type="submit">Apply filters</button>
            <a class="admin-filter-reset" href="{{ route('admin.people.index') }}">Clear</a>
            @can('route-admin-people-export')<button class="admin-filter-export" type="submit" formaction="{{ route('admin.people.export') }}">Export CSV</button>@endcan
        </form>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Person no.</th><th>Name</th><th>Phone</th><th>Assessments</th><th>Registered</th><th>Actions</th></tr></thead><tbody>
            @forelse($people as $person)<tr><td data-label="Person no.">#{{ $person->id }}</td><td data-label="Name"><strong>{{ $person->full_name }}</strong></td><td data-label="Phone">{{ $person->phone ?: '—' }}</td><td data-label="Assessments">{{ $person->assessments_count }}</td><td data-label="Registered">{{ $person->has_legacy_assessment ? 'Historical — date unknown' : $person->created_at?->format('d M Y') }}</td><td><div class="admin-person-actions">@can('route-admin-assessments-create')<a class="admin-person-action admin-person-action-assess" href="{{ route('admin.assessments.create', ['person_id' => $person->id]) }}">Add assessment</a>@endcan<button class="admin-person-action admin-person-action-view" type="button" data-dialog-open="#person-view-{{ $person->id }}">View</button>@can('route-admin-people-update')<button class="admin-person-action admin-person-action-edit" type="button" data-dialog-open="#person-edit-{{ $person->id }}">Edit</button>@endcan @can('route-admin-people-destroy')<button class="admin-person-action admin-person-action-delete" type="button" data-dialog-open="#person-delete-{{ $person->id }}">Delete</button>@endcan</div></td></tr>
            @empty <tr><td colspan="6">No people found.</td></tr> @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $people->links() }}</div>
        @can('route-admin-people-store')@include('admin.people._modal-form', ['person' => $newPerson, 'modalId' => 'person-create-dialog', 'creating' => true])@endcan
        @foreach($people as $person)
            @can('route-admin-people-update')@include('admin.people._modal-form', ['person' => $person, 'modalId' => 'person-edit-'.$person->id])@endcan
            @include('admin.people._record-modals', ['person' => $person])
        @endforeach
    </div>
</x-admin-layout>
