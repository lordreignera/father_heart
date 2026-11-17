<x-admin-layout title="Historical references">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Preserved historical data</p><h1>{{ ucfirst($category) }} references</h1><p>These are original records for review, not verified current accounts, locations, event details or consent.</p></div></div>
        @if($category === 'team')<p class="admin-help">Historical password hashes are never displayed or used for login. Create new staff accounts with appropriate roles and new credentials.</p>@endif
        @if($category === 'media')<p class="admin-help">These are old file references; their relationship to a person and permission to publish are not established.</p>@endif
        @if($category === 'events')
            <p class="admin-help">These are preserved registry entries. Event names and districts are historical references, not verified event dates or locations.</p>
            <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Source ID</th><th>Event name</th><th>District</th><th>Source</th></tr></thead><tbody>
                @forelse($records as $record)
                    <tr><td data-label="Source ID">{{ $record['id'] }}</td><td data-label="Event name">{{ is_scalar($record['fields']['distribution'] ?? null) && (string) $record['fields']['distribution'] !== '' ? $record['fields']['distribution'] : 'Not recorded' }}</td><td data-label="District">{{ is_scalar($record['fields']['district'] ?? null) && (string) $record['fields']['district'] !== '' ? $record['fields']['district'] : 'Not recorded' }}</td><td data-label="Source">{{ $record['table'] }}</td></tr>
                @empty <tr><td colspan="4">No historical event references found.</td></tr> @endforelse
            </tbody></table></div>
        @else
        @forelse($records as $record)
            <section class="admin-form-card admin-form-section"><h2>{{ str_replace('_', ' ', $record['table']) }} · #{{ $record['id'] }}</h2><dl class="admin-detail-list">@foreach($record['fields'] as $field => $value)<div><dt>{{ str_replace('_', ' ', $field) }}</dt><dd>{{ is_scalar($value) ? $value : '—' }}</dd></div>@endforeach</dl></section>
        @empty <p>No historical references found.</p> @endforelse
        @endif
        <div class="admin-pagination">{{ $records->links() }}</div>
    </div>
</x-admin-layout>
