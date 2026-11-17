<x-admin-layout :title="$staff->name">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ route('admin.users.index') }}">Back to staff accounts</a>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Staff account</p><h1>{{ $staff->name }}</h1><p>{{ $staff->email }}</p></div><a class="admin-primary-link" href="{{ route('admin.users.edit', $staff) }}">Edit account</a></div>
        <section class="admin-form-card"><h2>Assigned roles and access</h2>
            @forelse($staff->roles as $role)<div class="admin-access-role"><strong>{{ $role->name }}</strong><p>{{ $role->permissions->map(fn ($permission) => \App\Support\PermissionCatalogue::label($permission->name))->join(' · ') ?: 'No permissions assigned' }}</p></div>
            @empty <p>This account has no roles assigned.</p> @endforelse
            <p class="admin-help">Created {{ $staff->created_at?->format('d M Y, H:i') }}. Last updated {{ $staff->updated_at?->format('d M Y, H:i') }}.</p>
        </section>
        @if(! $staff->is(auth()->user()))
            <form class="admin-danger-zone" method="POST" action="{{ route('admin.users.destroy', $staff) }}" onsubmit="return confirm('Delete this staff account? This action cannot be undone.');">@csrf @method('DELETE')<div><strong>Delete staff account</strong><p>This prevents the account from signing in.</p></div><button class="admin-text-danger" type="submit">Delete account</button></form>
        @endif
    </div>
</x-admin-layout>
