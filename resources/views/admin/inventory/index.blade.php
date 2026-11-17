<x-admin-layout title="Inventory">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Stock management</p><h1>Inventory</h1><p>Record an approved opening count, then track stock received and issued.</p></div>@can('route-admin-inventory-items-store')<button class="admin-primary-link" type="button" data-dialog-open="#inventory-create">Add item</button>@endcan</div>
        @if(session('status'))<p class="admin-notice" role="status">{{ session('status') }}</p>@endif
        @if($errors->any() && !old('_modal'))<div class="admin-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="admin-filter" method="GET"><label for="inventory-search">Search items<input id="inventory-search" name="q" type="search" value="{{ request('q') }}" placeholder="Device or item"></label><button type="submit">Search</button><a class="admin-filter-reset" href="{{ route('admin.inventory.index') }}">Clear</a></form>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Item</th><th>Unit</th><th>Available</th><th>Opening count</th><th>Action</th></tr></thead><tbody>
            @forelse($items as $item)<tr><td data-label="Item"><strong>{{ $item->name }}</strong></td><td data-label="Unit">{{ $item->unit }}</td><td data-label="Available">{{ $item->opening_date ? number_format($item->quantity) : 'Not counted' }}</td><td data-label="Opening count">{{ $item->opening_date?->format('d M Y') ?: 'Awaiting approval' }}</td><td><div class="admin-person-actions">@can('route-admin-inventory-movements-store')<button class="admin-person-action admin-person-action-edit" type="button" data-dialog-open="#inventory-movement-{{ $item->id }}">{{ $item->opening_date ? 'Stock in / out' : 'Set opening count' }}</button>@endcan</div></td></tr>
            @empty<tr><td colspan="5">No inventory items yet. Add an item to get started.</td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $items->links() }}</div>
        <h2 class="admin-subheading">Recent stock movements</h2>
        @can('route-admin-inventory-movements-index')<p><a href="{{ route('admin.inventory.movements.index') }}">View all stock movements</a></p>@endcan
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Date</th><th>Item</th><th>Movement</th><th>Quantity</th><th>Reference</th><th>Recorded by</th></tr></thead><tbody>
            @forelse($movements as $movement)<tr><td data-label="Date">{{ $movement->occurred_on->format('d M Y') }}</td><td data-label="Item">{{ $movement->item->name }}</td><td data-label="Movement">{{ ['opening' => 'Opening count', 'in' => 'Stock in', 'out' => 'Stock out'][$movement->type] ?? $movement->type }}</td><td data-label="Quantity">{{ number_format($movement->quantity) }}</td><td data-label="Reference">{{ $movement->reference ?: '—' }}</td><td data-label="Recorded by">{{ $movement->recorder?->name ?: 'Account unavailable' }}</td></tr>
            @empty<tr><td colspan="6">No stock movements recorded yet.</td></tr>@endforelse
        </tbody></table></div>

        @can('route-admin-inventory-historical-index')<section class="admin-form-card admin-form-section"><h2>Historical stock references</h2><p class="admin-help">Old snapshots are not verified current stock or stock movements. Approve a physical count before setting any opening balance.</p><a class="admin-primary-link" href="{{ route('admin.inventory.historical.index') }}">View historical stock references</a></section>@endcan

        @can('route-admin-inventory-items-store')<dialog class="admin-dialog" id="inventory-create" aria-labelledby="inventory-create-title" @if(old('_modal') === 'inventory-create') open @endif>
            <div class="admin-dialog-head"><div><p class="admin-eyebrow">Inventory</p><h2 id="inventory-create-title">Add inventory item</h2><p>Add a device or supply to your stock list.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
            @if(old('_modal') === 'inventory-create' && $errors->any())<div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('admin.inventory.items.store') }}">@csrf<input type="hidden" name="_modal" value="inventory-create"><div class="admin-field-grid"><label>Item name<input name="name" required maxlength="160" value="{{ old('_modal') === 'inventory-create' ? old('name') : '' }}" placeholder="e.g. GEN 2 medium wheelchair"></label><label>Unit<input name="unit" required maxlength="40" value="{{ old('_modal') === 'inventory-create' ? old('unit', 'item') : 'item' }}"></label></div><div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button type="submit" class="admin-primary-link">Add item</button></div></form>
        </dialog>@endcan
        @can('route-admin-inventory-movements-store')@foreach($items as $item)
            @php($modalId = 'inventory-movement-'.$item->id)
            @php($returning = old('_modal') === $modalId)
            <dialog class="admin-dialog" id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" @if($returning) open @endif>
                <div class="admin-dialog-head"><div><p class="admin-eyebrow">Inventory</p><h2 id="{{ $modalId }}-title">{{ $item->opening_date ? 'Record stock movement' : 'Approve opening count' }}</h2><p>{{ $item->name }} · {{ $item->opening_date ? number_format($item->quantity).' currently available' : 'Opening count not yet recorded' }}</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
                @if($returning && $errors->any())<div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="POST" action="{{ route('admin.inventory.movements.store', $item) }}">@csrf<input type="hidden" name="_modal" value="{{ $modalId }}">
                    <div class="admin-field-grid">
                        @if($item->opening_date)<label>Movement<select name="type" required><option value="in" @selected($returning && old('type') === 'in')>Stock in</option><option value="out" @selected($returning && old('type') === 'out')>Stock out</option></select></label>@else<input type="hidden" name="type" value="opening">@endif
                        <label>Quantity<input name="quantity" type="number" min="{{ $item->opening_date ? 1 : 0 }}" max="1000000000" required value="{{ $returning ? old('quantity') : '' }}"></label>
                        <label>Date<input name="occurred_on" type="date" required max="{{ now('Africa/Kampala')->format('Y-m-d') }}" value="{{ $returning ? old('occurred_on') : now('Africa/Kampala')->format('Y-m-d') }}"></label>
                        <label>Reference <small>(optional)</small><input name="reference" maxlength="180" value="{{ $returning ? old('reference') : '' }}" placeholder="Receipt, mission or document number"></label>
                    </div>
                    <label class="admin-wide-label">Notes <small>(optional)</small><textarea name="notes" rows="2" maxlength="2000">{{ $returning ? old('notes') : '' }}</textarea></label>
                    @if(! $item->opening_date)<label class="inventory-confirm"><input type="checkbox" name="opening_confirmed" value="1" required> I confirm this opening balance is based on a reviewed and approved stock count.</label>@endif
                    <div class="admin-dialog-actions"><button class="admin-secondary-button" type="button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">{{ $item->opening_date ? 'Record movement' : 'Save opening count' }}</button></div>
                </form>
            </dialog>
        @endforeach @endcan
    </div>
</x-admin-layout>
