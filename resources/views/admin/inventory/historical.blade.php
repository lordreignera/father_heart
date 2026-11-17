<x-admin-layout title="Historical stock references">
    <div class="admin-content">
        <a class="admin-back" href="{{ route('admin.inventory.index') }}">Back to inventory</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Preserved historical data</p><h1>Historical stock references</h1><p>Old inventory snapshots and device records, shown with their original field names and values.</p></div></div>
        <p class="admin-help">These are unverified historical references, not current balances or confirmed movements. Dates retain their original format. Approve a physical count before setting opening stock.</p>
        @if($errors->any())<div class="admin-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="admin-filter" method="GET" action="{{ route('admin.inventory.historical.index') }}">
            <label for="historical-stock-search">Search all references<input id="historical-stock-search" name="q" type="search" maxlength="160" value="{{ request('q') }}" placeholder="Value, field or record ID"></label>
            <label for="historical-stock-source">Source table<select id="historical-stock-source" name="source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ str_replace('_', ' ', $source) }}</option>@endforeach</select></label>
            <button type="submit">Filter</button><a class="admin-filter-reset" href="{{ route('admin.inventory.historical.index') }}">Clear</a>
            @can('route-admin-inventory-historical-export')<button class="admin-filter-export" type="submit" formaction="{{ route('admin.inventory.historical.export') }}">Export all matching CSV (Excel)</button>@endcan
        </form>
        <p class="admin-help">{{ number_format($records->total()) }} matching historical records. Export includes all matching pages; historical dates are exported as text to prevent Excel guessing their format.</p>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table" data-table-tools="off"><thead><tr><th>Source table</th><th>Source record ID</th>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>
            @forelse($records as $record)
                <tr><td data-label="Source table"><strong>{{ $record['source'] }}</strong></td><td data-label="Source record ID">{{ $record['source_id'] }}</td>@foreach($columns as $column)<td data-label="{{ $column }}">{{ array_key_exists($column, $record['fields']) && $record['fields'][$column] !== '' ? $record['fields'][$column] : '—' }}</td>@endforeach</tr>
            @empty <tr><td colspan="{{ $columns->count() + 2 }}">No historical stock references match these filters.</td></tr> @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $records->links() }}</div>
    </div>
</x-admin-layout>
