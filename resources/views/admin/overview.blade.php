<x-admin-layout title="Dashboard">
    <div class="admin-content">
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        <div class="admin-intro"><div><p class="admin-eyebrow">Father's Heart · Administration</p><h1>Welcome back, {{ auth()->user()->name }}.</h1><p>Review registrations, ongoing assessments, planned missions and inventory movements.</p></div><span class="admin-role-badge">{{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'Staff access' }}</span></div>
        <form class="admin-filter dashboard-filter" method="GET" action="{{ route('dashboard') }}"><label for="dashboard-month">Reporting month<input id="dashboard-month" type="month" name="month" value="{{ $month->format('Y-m') }}" required></label><button type="submit">Apply filter</button><a class="admin-filter-reset" href="{{ route('dashboard') }}">Current month</a><p>Showing {{ $month->format('F Y') }} · Times use Kampala local time.</p></form>
        @if($errors->has('month')) <p class="admin-error" role="alert">{{ $errors->first('month') }}</p> @endif
        <div class="admin-stat-grid dashboard-stat-grid" aria-label="Monthly workspace summary">
            @can('route-admin-people-index')
                <section class="admin-stat-card"><span class="admin-stat-icon" aria-hidden="true">01</span><p>People registered</p><strong>{{ number_format($personCount) }}</strong><small>Registered in {{ $month->format('F Y') }}</small></section>
            @endcan
            @can('route-admin-assessments-index')
                <section class="admin-stat-card"><span class="admin-stat-icon" aria-hidden="true">02</span><p>Ongoing assessments</p><strong>{{ number_format($ongoingCount) }}</strong><small>Draft, assessed or waitlisted in this month</small></section>
            @endcan
            @can('route-admin-distributions-index')
                <section class="admin-stat-card"><span class="admin-stat-icon" aria-hidden="true">03</span><p>Upcoming / active events</p><strong>{{ number_format($events->count()) }}</strong><small>Planned or active during this month</small></section>
            @endcan
            @can('route-admin-inventory-index')
                <section class="admin-stat-card"><span class="admin-stat-icon" aria-hidden="true">04</span><p>Stock movements</p><strong>{{ number_format($stockIn) }} in / {{ number_format($stockOut) }} out</strong><small>Movement records in this month</small></section>
            @endcan
        </div>
        <div class="dashboard-panels">
            @can('route-admin-distributions-index')
                <section class="admin-form-card dashboard-panel"><div class="dashboard-panel-heading"><div><p class="admin-eyebrow">{{ $month->format('F Y') }}</p><h2>Distribution events</h2></div><a href="{{ route('admin.distributions.index') }}">All events →</a></div>
                    @forelse($events as $event)<div class="dashboard-event"><div><strong>{{ $event->name }}</strong><small>{{ $event->district?->name ?: 'Location not set' }} · {{ ucfirst($event->status) }}</small></div><span>{{ $event->event_date->format('d M') }}</span></div>@empty<p class="admin-help">No planned or active events in this month.</p>@endforelse
                </section>
            @endcan
            @can('route-admin-inventory-index')
                <section class="admin-form-card dashboard-panel"><div class="dashboard-panel-heading"><div><p class="admin-eyebrow">{{ $month->format('F Y') }}</p><h2>Inventory in &amp; out</h2></div><a href="{{ route('admin.inventory.index') }}">Open inventory →</a></div>
                    @forelse($stockByItem as $movement)<div class="dashboard-event"><div><strong>{{ $movement->item?->name ?: 'Item unavailable' }}</strong><small>{{ $movement->item?->unit ?: 'item' }}</small></div><span>+{{ number_format($movement->received) }} / −{{ number_format($movement->issued) }}</span></div>@empty<p class="admin-help">No stock movements in this month.</p>@endforelse
                    <p class="admin-help">{{ number_format($stockOnHand) }} item types have an approved opening count; {{ number_format($stockUncounted) }} still need one.</p>
                </section>
            @endcan
        </div>
        <div class="admin-section-heading"><div><p class="admin-eyebrow">Workspace</p><h2>Operational areas</h2></div></div>
        <div class="admin-module-grid">
            @can('route-admin-people-index')<section class="admin-module-card"><span class="admin-module-symbol">01</span><span class="admin-module-status">Available</span><h3>People &amp; registration</h3><p>Register people and review their care records.</p><p><a href="{{ route('admin.people.index') }}">Open people registry →</a></p></section>@endcan
            @can('route-admin-assessments-index')
            <section class="admin-module-card"><span class="admin-module-symbol">02</span><span class="admin-module-status">Available</span><h3>Assessment forms</h3><p>Record intake questionnaires and separate photo consent.</p><p><a href="{{ route('admin.assessments.index') }}">Open assessments →</a></p></section>@endcan
            @can('route-admin-distributions-index')<section class="admin-module-card"><span class="admin-module-symbol">03</span><span class="admin-module-status">Available</span><h3>Distribution events</h3><p>Plan missions and organize linked assessments.</p><p><a href="{{ route('admin.distributions.index') }}">Open events →</a></p></section>@endcan
            @can('route-admin-inventory-index')<section class="admin-module-card"><span class="admin-module-symbol">04</span><span class="admin-module-status">Available</span><h3>Inventory</h3><p>Manage approved opening counts and stock movements.</p><p><a href="{{ route('admin.inventory.index') }}">Open inventory →</a></p></section>@endcan
            @can('route-admin-testimonies-index')<section class="admin-module-card"><span class="admin-module-symbol">05</span><span class="admin-module-status">Private</span><h3>Testimony videos</h3><p>Upload and review testimonies privately.</p><p><a href="{{ route('admin.testimonies.index') }}">Open testimony videos →</a></p></section>@endcan
        </div>
        @can('route-admin-people-index')<aside class="admin-next-step"><div><span class="admin-next-icon" aria-hidden="true">→</span><div><strong>Historical data</strong><p>{{ $importedCount === 0 ? 'No legacy records imported yet. Historical data is being prepared for a reviewed transfer.' : number_format($importedCount).' historical source rows imported.' }}</p></div></div></aside>@endcan
    </div>
</x-admin-layout>
