<x-admin-layout title="Districts and cities">
    <div class="admin-content">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Locations</p><h1>Districts & cities</h1><p>Choose a district to manage its subcounties, town councils and divisions.</p></div>
            @can('route-admin-districts-store')<button class="admin-primary-link" type="button" data-dialog-open="#district-create-dialog">Add district</button>@endcan
        </div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <p class="admin-error" role="alert">{{ $errors->first() }}</p> @endif
        <form class="admin-filter" method="GET">
            <label for="district-search">Search districts</label>
            <input id="district-search" type="search" name="q" value="{{ request('q') }}" placeholder="District or city name">
            <button type="submit">Search</button>
        </form>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Name</th><th>Type</th><th>UBOS code</th><th>Subcounty-tier units</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($districts as $district)
                <tr>
                    <td><strong>{{ $district->name }}</strong></td><td>{{ ucfirst($district->kind) }}</td><td>{{ $district->ubos_code ?? '—' }}</td>
                    <td>@can('route-admin-subcounties-index')<a href="{{ route('admin.subcounties.index', ['district_id' => $district->id]) }}">{{ number_format($district->subcounties_count) }}</a>@else {{ number_format($district->subcounties_count) }} @endcan</td>
                    <td>{{ $district->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>@can('route-admin-districts-update')<button class="admin-table-action" type="button" data-dialog-open="#district-edit-{{ $district->id }}">Edit</button>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="6">No districts found. Seed locations or add one.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="admin-pagination">{{ $districts->links() }}</div>
        @can('route-admin-districts-store')@include('admin.locations.districts._modal-form', ['district' => $newDistrict, 'modalId' => 'district-create-dialog', 'creating' => true])@endcan
        @foreach($districts as $district)
            @can('route-admin-districts-update')@include('admin.locations.districts._modal-form', ['district' => $district, 'modalId' => 'district-edit-'.$district->id])@endcan
        @endforeach
    </div>
</x-admin-layout>
