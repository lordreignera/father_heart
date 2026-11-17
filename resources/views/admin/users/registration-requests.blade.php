<x-admin-layout title="Registration requests">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">User management</p><h1>Registration requests</h1><p>Review requests and choose the final role before creating an account.</p></div></div>
        @if(session('status'))<p class="admin-notice" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p class="admin-notice">The final role needs workspace access. Review its permissions before approving an account.</p>
        <form method="GET" class="admin-filter"><label for="status">Request status</label><select name="status" id="status"><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option><option value="rejected" @selected($status === 'rejected')>Rejected</option></select><button type="submit">Filter</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Requested role</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>
            @forelse($requests as $registration)
                <tr><td data-label="Name">{{ $registration->name }}</td><td data-label="Email">{{ $registration->email }}</td><td data-label="Requested role">{{ $registration->requested_role_name }}</td><td data-label="Submitted">{{ $registration->created_at?->format('d M Y') }}</td><td data-label="Actions">
                    @if($status === 'pending')
                        <button type="button" class="admin-person-action admin-person-action-edit" data-dialog-open="#registration-approve-{{ $registration->id }}">Review &amp; approve</button>
                        <button type="button" class="admin-person-action admin-person-action-delete" data-dialog-open="#registration-reject-{{ $registration->id }}">Reject</button>
                    @else {{ ucfirst($registration->status) }} @endif
                </td></tr>
            @empty <tr><td colspan="5">No {{ $status }} requests found.</td></tr> @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $requests->links() }}</div>
        @if($status === 'pending') @foreach($requests as $registration)
            <dialog class="admin-dialog" id="registration-approve-{{ $registration->id }}" aria-labelledby="approve-title-{{ $registration->id }}">
                <div class="admin-dialog-head"><div><p class="admin-eyebrow">Registration request</p><h2 id="approve-title-{{ $registration->id }}">Approve {{ $registration->name }}?</h2><p>{{ $registration->email }} requested {{ $registration->requested_role_name }}.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close">&times;</button></div>
                <form class="admin-dialog-form" method="POST" action="{{ route('admin.registration-requests.approve', $registration) }}">@csrf
                    <div class="admin-field-grid"><label>Final role<select name="role_id" required><option value="">Select a role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($role->id === $registration->requested_role_id)>{{ $role->name }}</option>@endforeach</select></label></div>
                    <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button type="submit" class="admin-primary-link">Approve and create account</button></div>
                </form>
            </dialog>
            <dialog class="admin-dialog" id="registration-reject-{{ $registration->id }}" aria-labelledby="reject-title-{{ $registration->id }}">
                <div class="admin-dialog-head"><div><p class="admin-eyebrow">Registration request</p><h2 id="reject-title-{{ $registration->id }}">Reject {{ $registration->name }}?</h2></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close">&times;</button></div>
                <p>No account will be created. The applicant can submit a new request later.</p><div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><form method="POST" action="{{ route('admin.registration-requests.reject', $registration) }}">@csrf<button type="submit" class="admin-person-delete-confirm">Reject request</button></form></div>
            </dialog>
        @endforeach @endif
    </div>
</x-admin-layout>
