<x-admin-layout title="Role permissions">
    <div class="admin-content">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Workspace access</p><h1>Role permissions</h1><p>Choose a role and its permitted actions.</p></div>@if(auth()->user()->isSuperAdmin())<a class="admin-primary-link" href="{{ route('admin.permissions.create') }}">Add custom permission</a>@endif</div>
        @if(session('status')) <p class="admin-notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <div class="admin-error" role="alert">{{ $errors->first() }}</div> @endif
        <div class="admin-access-tabs"><a href="{{ route('admin.roles.index') }}">Roles</a><a class="is-current" href="{{ route('admin.permissions.index') }}">Role permissions</a></div>
        <form method="GET" class="admin-filter admin-role-filter"><label for="permission-role">Choose role<select id="permission-role" name="role">@foreach($roles as $role)<option value="{{ $role->id }}" @selected($selectedRole?->id === $role->id)>{{ $role->name }}</option>@endforeach</select></label><button type="submit">Show permissions</button></form>
        @if($selectedRole)
            @php($editable = !in_array($selectedRole->name, ['Super Administrator', 'super-admin'], true) && \App\Support\RoleAccess::canManage(auth()->user(), $selectedRole) && auth()->user()->can('route-admin-roles-permissions-update'))
            <form class="admin-form-card" method="POST" action="{{ route('admin.roles.permissions.update', $selectedRole) }}" data-permission-editor>@csrf @method('PUT')
                <h2>{{ $selectedRole->name }}</h2><p>Grant <strong>Access the staff workspace</strong> and the required actions, including both opening forms and saving. Changes affect everyone with this role; some actions have additional safeguards.</p>
                @if(\App\Support\RegistrationRoles::isVolunteer($selectedRole))<p class="admin-notice">Individual record assignments are not yet implemented. These permissions can allow volunteers to access all records in the permitted areas.</p>@endif
                @unless($editable)<p class="admin-notice">This role is protected or you cannot manage its permissions. Its permissions are read-only.</p>@endunless
                <div class="admin-permission-tools" data-permission-tools hidden>
                    <label for="permission-search">Search permissions<input id="permission-search" type="search" placeholder="Search by module or action" data-permission-search></label>
                    @if($editable)<label class="admin-permission-select"><input type="checkbox" data-permission-select-all> Select all matching permissions</label>@endif
                    <small>Selection applies to matching permissions, including collapsed modules. Hidden permissions keep their current selection.</small><p role="status" data-permission-status></p>
                </div>
                @foreach(\App\Support\PermissionCatalogue::byModule($editable ? $assignablePermissions : $permissions) as $group => $items)
                    <details class="admin-permission-module" data-permission-module data-module-name="{{ $group }}"><summary><span>{{ $group }}</span><small>{{ $items->count() }} permissions</small></summary>
                        @if($editable)<label class="admin-permission-select" data-module-select-wrap hidden><input type="checkbox" data-module-select> Select all matching permissions in {{ $group }}</label>@endif
                        <div class="admin-permission-grid">
                        @foreach($items as $permission)<label data-permission-row><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(collect(old('permissions', $selectedRole->permissions->pluck('name')->all()))->contains($permission->name)) @disabled(!$editable)><span><strong>{{ \App\Support\PermissionCatalogue::label($permission->name) }}</strong>@if($description = \App\Support\PermissionCatalogue::description($permission->name))<small>{{ $description }}</small>@endif</span></label>@endforeach
                        </div>
                    </details>
                @endforeach
                <p class="admin-muted" data-permission-empty hidden>No matching permissions found.</p>
                @if($editable)@can('route-admin-roles-permissions-update')<div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save permissions</button></div>@endcan @endif
            </form>
        @else <p>No roles configured. Create a role first.</p> @endif
        <details class="admin-permission-module"><summary>Permission catalogue</summary><p>Custom keys only grant access where application code checks them.</p>
        <div class="admin-table-wrap"><table class="admin-table admin-people-table"><thead><tr><th>Permission</th><th>Roles using it</th><th>Type</th><th>Actions</th></tr></thead><tbody>
            @forelse($permissions as $permission)
                <tr><td data-label="Permission"><strong>{{ \App\Support\PermissionCatalogue::label($permission->name) }}</strong></td><td data-label="Roles">{{ $permission->roles_count }}</td><td data-label="Type">{{ \App\Support\PermissionCatalogue::isBuiltIn($permission->name) ? 'Built-in' : 'Custom' }}</td><td>@if(!\App\Support\PermissionCatalogue::isBuiltIn($permission->name) && auth()->user()->isSuperAdmin()) <a href="{{ route('admin.permissions.edit', $permission) }}">Edit</a> @else <span class="admin-muted">Protected</span> @endif</td></tr>
            @empty <tr><td colspan="4">No permissions configured.</td></tr> @endforelse
        </tbody></table></div></details>
    </div>
</x-admin-layout>
