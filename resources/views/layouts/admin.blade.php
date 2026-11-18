<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-pwa-head />
    <title>{{ $title ?? 'Dashboard' }} | Father's Heart Mobility Ministry</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin-locations.js', 'resources/js/admin-table-tools.js'])
    @if(request()->routeIs('profile.show')) @livewireStyles @endif
</head>
<body class="admin-body" @if(auth()->user()->isSuperAdmin()) data-table-page-export="true" @endif>
    <a class="admin-skip" href="#main-content">Skip to content</a>
    <div class="admin-frame">
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <a class="admin-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/fathers-heart-logo.png') }}" alt="" width="44" height="44">
                <span><strong>Father's Heart</strong><small>Mobility Ministry</small></span>
            </a>
            <div class="admin-side-label">Workspace</div>
            <nav class="admin-nav" aria-label="Workspace">
                <a class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><span aria-hidden="true">▦</span> Overview</a>
                @canany(['route-admin-people-index', 'route-admin-assessments-index', 'route-admin-historical-media', 'route-admin-historical-feedback-index'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.people.*', 'admin.assessments.*', 'admin.historical.feedback.*') || request()->route('category') === 'media') open @endif><summary><span aria-hidden="true">♙</span> People &amp; care</summary><div class="admin-nav-group-links">
                        @can('route-admin-people-index')<a class="{{ request()->routeIs('admin.people.*') ? 'is-current' : '' }}" href="{{ route('admin.people.index') }}">People</a>@endcan
                        @can('route-admin-assessments-index')<a class="{{ request()->routeIs('admin.assessments.*') ? 'is-current' : '' }}" href="{{ route('admin.assessments.index') }}">Assessments</a>@endcan
                        @can('route-admin-historical-media')<a class="{{ request()->route('category') === 'media' ? 'is-current' : '' }}" href="{{ route('admin.historical.index', 'media') }}">Historical media</a>@endcan
                        @can('route-admin-historical-feedback-index')<a class="{{ request()->routeIs('admin.historical.feedback.*') ? 'is-current' : '' }}" href="{{ route('admin.historical.feedback.index') }}">Historical feedback</a>@endcan
                    </div></details>
                @endcanany
                @canany(['route-admin-therapy-children-index', 'route-admin-therapy-reports-index', 'route-admin-therapy-services-index', 'route-admin-therapy-options-index'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.therapy.*')) open @endif><summary><span aria-hidden="true">T</span> Children’s therapy</summary><div class="admin-nav-group-links">
                        @can('route-admin-therapy-children-index')<a href="{{ route('admin.therapy.children.index') }}">Children</a>@endcan
                        @can('route-admin-therapy-reports-index')<a href="{{ route('admin.therapy.reports.index') }}">Visit reports</a>@endcan
                        @can('route-admin-therapy-services-index')<a href="{{ route('admin.therapy.services.index') }}">Therapy settings</a>@endcan
                        @can('route-admin-therapy-options-index')<a href="{{ route('admin.therapy.options.index') }}">Registration options</a>@endcan
                    </div></details>
                @endcanany
                @canany(['route-admin-distributions-index', 'route-admin-historical-events'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.distributions.*') || request()->route('category') === 'events') open @endif><summary><span aria-hidden="true">▥</span> Distributions</summary><div class="admin-nav-group-links">
                        @can('route-admin-distributions-index')<a class="{{ request()->routeIs('admin.distributions.*') ? 'is-current' : '' }}" href="{{ route('admin.distributions.index') }}">Distribution events</a>@endcan
                        @can('route-admin-historical-events')<a class="{{ request()->route('category') === 'events' ? 'is-current' : '' }}" href="{{ route('admin.historical.index', 'events') }}">Historical events</a>@endcan
                    </div></details>
                @endcanany
                @canany(['route-admin-inventory-index', 'route-admin-inventory-historical-index', 'route-admin-inventory-movements-index'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.inventory.*')) open @endif><summary><span aria-hidden="true">I</span> Inventory</summary><div class="admin-nav-group-links">
                        @can('route-admin-inventory-index')<a class="{{ request()->routeIs('admin.inventory.index') ? 'is-current' : '' }}" href="{{ route('admin.inventory.index') }}">Stock management</a>@endcan
                        @can('route-admin-inventory-movements-index')<a class="{{ request()->routeIs('admin.inventory.movements.index') ? 'is-current' : '' }}" href="{{ route('admin.inventory.movements.index') }}">Movement history</a>@endcan
                        @can('route-admin-inventory-historical-index')<a class="{{ request()->routeIs('admin.inventory.historical.*') ? 'is-current' : '' }}" href="{{ route('admin.inventory.historical.index') }}">Historical stock</a>@endcan
                    </div></details>
                @endcanany
                @canany(['route-admin-users-index', 'route-admin-historical-team-index', 'route-admin-roles-index', 'route-admin-permissions-index', 'route-admin-registration-requests-index'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.users.*', 'admin.registration-requests.*', 'admin.historical.team.*', 'admin.roles.*', 'admin.permissions.*')) open @endif><summary><span aria-hidden="true">U</span> User management</summary><div class="admin-nav-group-links">
                        @can('route-admin-users-index')<a class="{{ request()->routeIs('admin.users.*') ? 'is-current' : '' }}" href="{{ route('admin.users.index') }}">Staff accounts</a>@endcan
                        @if(auth()->user()->isSuperAdmin())<a class="{{ request()->routeIs('admin.registration-requests.*') ? 'is-current' : '' }}" href="{{ route('admin.registration-requests.index') }}">Registration requests</a>@endif
                        @can('route-admin-historical-team-index')<a class="{{ request()->routeIs('admin.historical.team.*') ? 'is-current' : '' }}" href="{{ route('admin.historical.team.index') }}">Historical team</a>@endcan
                        @can('route-admin-roles-index')<a class="{{ request()->routeIs('admin.roles.*') ? 'is-current' : '' }}" href="{{ route('admin.roles.index') }}">Roles</a>@endcan
                        @can('route-admin-permissions-index')<a class="{{ request()->routeIs('admin.permissions.*') ? 'is-current' : '' }}" href="{{ route('admin.permissions.index') }}">Role permissions</a>@endcan
                    </div></details>
                @endcanany
                @canany(['route-admin-districts-index', 'route-admin-subcounties-index', 'route-admin-historical-locations', 'route-admin-settings-intake-code-edit', 'route-admin-therapy-catalogues-index'])
                    <details class="admin-nav-group" @if(request()->routeIs('admin.districts.*', 'admin.subcounties.*', 'admin.settings.intake-code.*', 'admin.therapy.options.*', 'admin.therapy.catalogues.*') || request()->route('category') === 'locations') open @endif><summary><span aria-hidden="true">⌖</span> Locations &amp; settings</summary><div class="admin-nav-group-links">
                        @can('route-admin-districts-index')<a class="{{ request()->routeIs('admin.districts.*') ? 'is-current' : '' }}" href="{{ route('admin.districts.index') }}">Districts &amp; cities</a>@endcan
                        @can('route-admin-subcounties-index')<a class="{{ request()->routeIs('admin.subcounties.*') ? 'is-current' : '' }}" href="{{ route('admin.subcounties.index') }}">Subcounties</a>@endcan
                        @can('route-admin-historical-locations')<a class="{{ request()->route('category') === 'locations' ? 'is-current' : '' }}" href="{{ route('admin.historical.index', 'locations') }}">Historical locations</a>@endcan
                        @can('route-admin-settings-intake-code-edit')<a class="{{ request()->routeIs('admin.settings.intake-code.*') ? 'is-current' : '' }}" href="{{ route('admin.settings.intake-code.edit') }}">Intake settings</a>@endcan
                        @can('route-admin-therapy-catalogues-index')@foreach(\App\Support\TherapyCatalogues::ITEMS as $key => $definition)<a class="{{ request()->routeIs('admin.therapy.catalogues.*') && request()->route('catalogue') === $key ? 'is-current' : '' }}" href="{{ route('admin.therapy.catalogues.index', $key) }}">{{ $definition['label'] }}</a>@endforeach @endcan
                    </div></details>
                @endcanany
                <a class="{{ request()->routeIs('user-guide') ? 'is-current' : '' }}" href="{{ route('user-guide') }}"><span aria-hidden="true">?</span> User guide</a>
                <a class="{{ request()->routeIs('profile.show') ? 'is-current' : '' }}" href="{{ route('profile.show') }}" @if(request()->routeIs('profile.show')) aria-current="page" @endif><span aria-hidden="true">◎</span> My profile</a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener"><span aria-hidden="true">↗</span> View website</a>
            </nav>
            <div class="admin-side-footer">
                <span class="admin-access-dot" aria-hidden="true"></span>
                <span>Secure staff workspace</span>
            </div>
        </aside>

        <div class="admin-main-wrap">
            <header class="admin-topbar">
                <span class="admin-topbar-title">{{ request()->routeIs('profile.show') ? 'My profile' : 'Ministry overview' }}</span>
                <div class="admin-topbar-actions">
                    <button class="admin-install" type="button" data-install-app hidden>Install app</button>
                    <span class="admin-user-name">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="admin-signout" type="submit">Sign out</button>
                    </form>
                </div>
            </header>
            <main id="main-content" class="admin-main">
                {{ $slot }}
            </main>
        </div>
    </div>
    @if(request()->routeIs('profile.show')) @stack('modals') @livewireScripts @endif
</body>
</html>
