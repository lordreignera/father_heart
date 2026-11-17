<x-admin-layout title="Staff accounts">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Workspace access</p><h1>Staff accounts</h1><p>Create and maintain the accounts your team uses to access this workspace.</p></div>@can('route-admin-users-store')<button type="button" class="admin-primary-link" data-dialog-open="#staff-create">Add staff account</button>@endcan</div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any() && !old('_modal')) <div class="admin-error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="GET" class="admin-filter"><label for="staff-search">Search staff</label><input id="staff-search" type="search" name="q" value="{{ request('q') }}" placeholder="Name or email"><button type="submit">Search</button></form>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
            @forelse($users as $staff)
                @php($canManage = \App\Support\RoleAccess::canManageUser(auth()->user(), $staff))
                <tr><td data-label="Name"><strong>{{ $staff->name }}</strong>@if($staff->is(auth()->user())) <small>(you)</small> @endif</td><td data-label="Email">{{ $staff->email }}</td><td data-label="Roles">{{ $staff->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</td><td data-label="Joined">{{ $staff->created_at?->format('d M Y') }}</td><td><div class="admin-person-actions"><button type="button" class="admin-person-action admin-person-action-view" data-dialog-open="#staff-view-{{ $staff->id }}">View</button>@if($canManage)@can('route-admin-users-update')<button type="button" class="admin-person-action admin-person-action-edit" data-dialog-open="#staff-edit-{{ $staff->id }}">Edit</button>@endcan @if(!$staff->is(auth()->user()))@can('route-admin-users-destroy')<button type="button" class="admin-person-action admin-person-action-delete" data-dialog-open="#staff-delete-{{ $staff->id }}">Delete</button>@endcan @endif @else <span class="admin-muted">Protected</span> @endif</div></td></tr>
            @empty <tr><td colspan="5">No staff accounts found.</td></tr> @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $users->links() }}</div>
        @can('route-admin-users-store')@include('admin.users._modal-form', ['staff' => new \App\Models\User, 'modalId' => 'staff-create', 'creating' => true])@endcan
        @foreach($users as $staff)
            <dialog class="admin-dialog" id="staff-view-{{ $staff->id }}" aria-labelledby="staff-view-title-{{ $staff->id }}"><div class="admin-dialog-head"><div><p class="admin-eyebrow">Staff account</p><h2 id="staff-view-title-{{ $staff->id }}">{{ $staff->name }}</h2><p>Account details and assigned roles.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div><dl class="admin-person-details"><div><dt>Email</dt><dd>{{ $staff->email }}</dd></div><div><dt>Roles</dt><dd>{{ $staff->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</dd></div><div><dt>Joined</dt><dd>{{ $staff->created_at?->format('d M Y') ?: 'Unknown' }}</dd></div></dl><div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Close</button><a class="admin-primary-link" href="{{ route('admin.users.show', $staff) }}">View account</a></div></dialog>
            @if(\App\Support\RoleAccess::canManageUser(auth()->user(), $staff))
                @can('route-admin-users-update')
                @include('admin.users._modal-form', ['staff' => $staff, 'modalId' => 'staff-edit-'.$staff->id, 'creating' => false])
                @endcan
                @if(!$staff->is(auth()->user()))
                    @can('route-admin-users-destroy')
                    <dialog class="admin-dialog" id="staff-delete-{{ $staff->id }}" aria-labelledby="staff-delete-title-{{ $staff->id }}"><div class="admin-dialog-head"><div><p class="admin-eyebrow">Staff account</p><h2 id="staff-delete-title-{{ $staff->id }}">Delete {{ $staff->name }}?</h2></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div><p>This permanently deletes the login account and may unlink it from a historical team entry. It cannot be undone here.</p><div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><form method="POST" action="{{ route('admin.users.destroy', $staff) }}">@csrf @method('DELETE')<button class="admin-person-delete-confirm" type="submit">Delete account</button></form></div></dialog>
                    @endcan
                @endif
            @endif
        @endforeach
    </div>
</x-admin-layout>
