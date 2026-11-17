<x-admin-layout title="Subcounties">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Locations</p><h1>Subcounties & local units</h1><p>Includes subcounties, town councils and city divisions.</p></div>@can('route-admin-subcounties-store')<button class="admin-primary-link" type="button" data-dialog-open="#subcounty-create-dialog">Add local unit</button>@endcan</div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        <form class="admin-filter" method="GET">
            <label for="unit-district">District/city</label><select id="unit-district" name="district_id"><option value="">All</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}{{ $district->kind === 'city' ? ' City' : '' }}</option>@endforeach</select>
            <label for="unit-search">Name</label><input id="unit-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search local units"><button type="submit">Filter</button>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>District/city</th><th>Type</th><th>UBOS code</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($subcounties as $subcounty)
                <tr><td><strong>{{ $subcounty->name }}</strong></td><td>{{ $subcounty->district->name }}</td><td>{{ str_replace('_', ' ', ucfirst($subcounty->kind)) }}</td><td>{{ $subcounty->ubos_code ?? '—' }}</td><td>{{ $subcounty->is_active ? 'Active' : 'Inactive' }}</td><td>@can('route-admin-subcounties-update')<button class="admin-table-action" type="button" data-dialog-open="#subcounty-edit-{{ $subcounty->id }}">Edit</button>@endcan</td></tr>
            @empty <tr><td colspan="6">No local units found.</td></tr> @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $subcounties->links() }}</div>
        @can('route-admin-subcounties-store')@include('admin.locations.subcounties._modal-form', ['subcounty' => $newSubcounty, 'districts' => $activeDistricts, 'modalId' => 'subcounty-create-dialog', 'creating' => true])@endcan
        @foreach($subcounties as $subcounty)
            @can('route-admin-subcounties-update')@include('admin.locations.subcounties._modal-form', ['subcounty' => $subcounty, 'districts' => $activeDistricts, 'modalId' => 'subcounty-edit-'.$subcounty->id])@endcan
        @endforeach
    </div>
</x-admin-layout>
