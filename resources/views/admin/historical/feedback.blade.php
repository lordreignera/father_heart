<x-admin-layout title="Historical feedback">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Preserved historical data</p><h1>Historical feedback</h1><p>Review feedback imported from the old system. Staff and recipient values are historical references and have not been verified as current records.</p></div><a class="admin-table-action" href="{{ route('admin.historical.feedback.index', $archived ? [] : ['archived' => 1]) }}">{{ $archived ? 'Active entries' : 'Archived entries' }}</a></div>
        <p class="admin-help">The original historical record is preserved. Deleting an entry archives it so a super administrator can restore it later.</p>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any() && !old('_modal')) <div class="admin-error" role="alert">{{ $errors->first() }}</div> @endif
        <form class="admin-filter" method="GET" action="{{ route('admin.historical.feedback.index') }}"><input type="hidden" name="archived" value="{{ $archived ? 1 : 0 }}"><label for="feedback-search">Search all feedback<input id="feedback-search" type="search" name="q" maxlength="120" value="{{ request('q') }}" placeholder="Source number, staff, recipient or rating"></label><button type="submit">Filter</button><a class="admin-filter-reset" href="{{ route('admin.historical.feedback.index', $archived ? ['archived' => 1] : []) }}">Clear</a>@can('route-admin-historical-feedback-export')<button class="admin-filter-export" type="submit" formaction="{{ route('admin.historical.feedback.export') }}">Export all matching CSV (Excel)</button>@endcan</form>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table" data-table-tools="off"><thead><tr><th>Source no.</th><th>Staff reference</th><th>Recipient reference</th><th>Rating / feedback</th><th>Actions</th></tr></thead><tbody>
            @forelse($records as $record)
                <tr><td data-label="Source no.">#{{ $record['source_id'] }}</td><td data-label="Staff reference">{{ $record['staff'] !== null && $record['staff'] !== '' ? $record['staff'] : 'Not recorded' }}</td><td data-label="Recipient reference">{{ $record['recipient'] !== null && $record['recipient'] !== '' ? $record['recipient'] : 'Not recorded' }}</td><td data-label="Rating / feedback">{{ $record['rating'] !== null && $record['rating'] !== '' ? \Illuminate\Support\Str::limit($record['rating'], 100) : 'Not recorded' }}</td><td><div class="admin-person-actions">
                    <button type="button" class="admin-person-action admin-person-action-view" data-dialog-open="#feedback-view-{{ $record['id'] }}">View</button>
                    @if(auth()->user()->isSuperAdmin())
                        @if($archived)<form method="POST" action="{{ route('admin.historical.feedback.restore', $record['id']) }}">@csrf<button type="submit" class="admin-person-action admin-person-action-edit">Restore</button></form>
                        @else<button type="button" class="admin-person-action admin-person-action-edit" data-dialog-open="#feedback-edit-{{ $record['id'] }}">Edit</button><button type="button" class="admin-person-action admin-person-action-delete" data-dialog-open="#feedback-delete-{{ $record['id'] }}">Delete</button>@endif
                    @endif
                </div></td></tr>
            @empty <tr><td colspan="5">No {{ $archived ? 'archived ' : '' }}historical feedback found.</td></tr> @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $records->links() }}</div>
        @foreach($records as $record)
            <dialog class="admin-dialog" id="feedback-view-{{ $record['id'] }}" aria-labelledby="feedback-view-title-{{ $record['id'] }}">
                <div class="admin-dialog-head"><div><p class="admin-eyebrow">Historical feedback</p><h2 id="feedback-view-title-{{ $record['id'] }}">Source #{{ $record['source_id'] }}</h2><p>References shown as recorded; identities are not verified.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
                <dl class="admin-person-details"><div><dt>Staff reference</dt><dd>{{ $record['staff'] !== null && $record['staff'] !== '' ? $record['staff'] : 'Not recorded' }}</dd></div><div><dt>Recipient reference</dt><dd>{{ $record['recipient'] !== null && $record['recipient'] !== '' ? $record['recipient'] : 'Not recorded' }}</dd></div><div><dt>Rating / feedback</dt><dd class="admin-preserve-lines">{{ $record['rating'] !== null && $record['rating'] !== '' ? $record['rating'] : 'Not recorded' }}</dd></div></dl>
                <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Close</button></div>
            </dialog>
            @if(auth()->user()->isSuperAdmin() && !$archived)
                @php($editModal = 'feedback-edit-'.$record['id'])
                <dialog class="admin-dialog" id="{{ $editModal }}" aria-labelledby="{{ $editModal }}-title" @if(old('_modal') === $editModal) open @endif>
                    <div class="admin-dialog-head"><div><p class="admin-eyebrow">Historical feedback</p><h2 id="{{ $editModal }}-title">Edit feedback</h2><p>The encrypted original remains unchanged. Do not assume references identify a particular person.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
                    @if(old('_modal') === $editModal && $errors->any()) <div class="admin-error" role="alert">{{ $errors->first() }}</div> @endif
                    <form method="POST" action="{{ route('admin.historical.feedback.update', $record['id']) }}">@csrf @method('PUT')<input type="hidden" name="_modal" value="{{ $editModal }}">
                        <div class="admin-field-grid"><label>Staff reference<input name="staff" maxlength="255" value="{{ old('_modal') === $editModal ? old('staff') : $record['staff'] }}"></label><label>Recipient reference<input name="recipient" maxlength="255" value="{{ old('_modal') === $editModal ? old('recipient') : $record['recipient'] }}"></label><label>Rating / feedback<textarea name="rating" maxlength="10000" rows="4">{{ old('_modal') === $editModal ? old('rating') : $record['rating'] }}</textarea></label></div>
                        <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">Save changes</button></div>
                    </form>
                </dialog>
                <dialog class="admin-dialog" id="feedback-delete-{{ $record['id'] }}" aria-labelledby="feedback-delete-title-{{ $record['id'] }}"><div class="admin-dialog-head"><div><p class="admin-eyebrow">Historical feedback</p><h2 id="feedback-delete-title-{{ $record['id'] }}">Archive this entry?</h2></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div><p>This removes the entry from the active list. The encrypted original is retained and the entry can be restored.</p><div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><form method="POST" action="{{ route('admin.historical.feedback.archive', $record['id']) }}">@csrf @method('DELETE')<button class="admin-person-delete-confirm" type="submit">Archive entry</button></form></div></dialog>
            @endif
        @endforeach
    </div>
</x-admin-layout>
