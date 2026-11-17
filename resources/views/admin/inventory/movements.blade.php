<x-admin-layout title="Inventory movement history">
    <div class="admin-content">
        @can('route-admin-inventory-index')<a class="admin-back" href="{{ route('admin.inventory.index') }}">Back to inventory</a>@endcan
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Stock management</p><h1>Movement history</h1><p>Review recorded opening counts and stock received or issued. Historical snapshots are kept separately.</p></div></div>
        @if($errors->any())<div class="admin-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="admin-filter" method="GET" action="{{ route('admin.inventory.movements.index') }}">
            <label>Item or reference<input type="search" name="q" maxlength="160" value="{{ request('q') }}"></label>
            <label>Movement<select name="type"><option value="">All movements</option>@foreach(['opening' => 'Opening count', 'in' => 'Stock in', 'out' => 'Stock out'] as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <button type="submit">Filter</button><a class="admin-filter-reset" href="{{ route('admin.inventory.movements.index') }}">Clear</a>
            @can('route-admin-inventory-movements-export')<button class="admin-filter-export" type="submit" formaction="{{ route('admin.inventory.movements.export') }}">Export all matching CSV (Excel)</button>@endcan
        </form>
        <p class="admin-help">{{ number_format($movements->total()) }} matching movements. Opening counts are shown separately from stock in.</p>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table" data-table-tools="off"><thead><tr><th>Date</th><th>Item</th><th>Movement</th><th>Quantity</th><th>Reference</th><th>Notes</th><th>Recorded by</th></tr></thead><tbody>
            @forelse($movements as $movement)<tr><td data-label="Date">{{ $movement->occurred_on->format('d M Y') }}</td><td data-label="Item">{{ $movement->item?->name ?: 'Item unavailable' }}</td><td data-label="Movement">{{ ['opening' => 'Opening count', 'in' => 'Stock in', 'out' => 'Stock out'][$movement->type] ?? $movement->type }}</td><td data-label="Quantity">{{ number_format($movement->quantity) }} {{ $movement->item?->unit }}</td><td data-label="Reference">{{ $movement->reference ?: '—' }}</td><td data-label="Notes">{{ $movement->notes ?: '—' }}</td><td data-label="Recorded by">{{ $movement->recorder?->name ?: 'Account unavailable' }}</td></tr>@empty<tr><td colspan="7">No movements match these filters.</td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $movements->links() }}</div>
    </div>
</x-admin-layout>
