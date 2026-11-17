<x-admin-layout title="Dashboard">
    <div class="admin-content">
        <div class="admin-intro">
            <div>
                <p class="admin-eyebrow">Father's Heart · Administration</p>
                <h1>Welcome back, {{ auth()->user()->name }}.</h1>
                <p>Manage locations, people and intake assessments here. Historical records will appear only after a verified import.</p>
            </div>
            <span class="admin-role-badge">{{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'Staff access' }}</span>
        </div>

        <div class="admin-stat-grid" aria-label="Workspace summary">
            <section class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">✦</span>
                <p>User accounts</p>
                <strong>{{ number_format($staffCount) }}</strong>
                <small>Users in this Laravel system</small>
            </section>
            <section class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">♿</span>
                <p>People registry</p>
                <strong>{{ number_format($personCount) }}</strong>
                <small>{{ $importedCount === 0 ? 'No legacy records imported yet' : number_format($importedCount).' historical rows imported' }}</small>
            </section>
            <section class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">▤</span>
                <p>Assessments</p>
                <strong>{{ number_format($assessmentCount) }}</strong>
                <small>Completed or in progress</small>
            </section>
        </div>

        <div class="admin-section-heading">
            <div>
                <p class="admin-eyebrow">Workspace</p>
                <h2>Operational areas</h2>
            </div>
            <p>{{ number_format($districtCount) }} districts/cities · {{ number_format($subcountyCount) }} local units · {{ number_format($eventCount) }} events</p>
        </div>

        <div class="admin-module-grid">
            <section class="admin-module-card">
                <span class="admin-module-symbol" aria-hidden="true">01</span>
                <span class="admin-module-status">Available</span>
                <h3>People & registration</h3>
                <p>Register people, record their needs, and keep each person's information organized.</p>
                <p><a href="{{ route('admin.people.index') }}">Open people registry →</a></p>
            </section>
            <section class="admin-module-card">
                <span class="admin-module-symbol" aria-hidden="true">02</span>
                <span class="admin-module-status">Available</span>
                <h3>Assessment forms</h3>
                <p>Record the current intake questionnaire and separate photo consent.</p>
                <p><a href="{{ route('admin.assessments.index') }}">Open assessments →</a></p>
            </section>
            <section class="admin-module-card">
                <span class="admin-module-symbol" aria-hidden="true">03</span>
                <span class="admin-module-status">Available</span>
                <h3>Uganda locations</h3>
                <p>Manage the district/city and subcounty-tier lists used by intake.</p>
                <p><a href="{{ route('admin.districts.index') }}">Open locations →</a></p>
            </section>
            <section class="admin-module-card">
                <span class="admin-module-symbol" aria-hidden="true">04</span>
                <span class="admin-module-status">Planned</span>
                <h3>Import, stock & reports</h3>
                <p>These remain pending until historical records and stock balances are reconciled.</p>
            </section>
        </div>

        <aside class="admin-next-step">
            <div>
                <span class="admin-next-icon" aria-hidden="true">→</span>
                <div><strong>Old data remains untouched</strong><p>The historical database is separate. No person, handover or stock value has been inferred from it yet.</p></div>
            </div>
            <a href="{{ route('profile.show') }}">Manage my account <span aria-hidden="true">↗</span></a>
        </aside>
    </div>
</x-admin-layout>
