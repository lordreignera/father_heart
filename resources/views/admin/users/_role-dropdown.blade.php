@php($selectedRoleIds = collect($selectedRoleIds ?? []))
<fieldset class="admin-role-picker admin-role-dropdown"><legend>Roles</legend>
    @if($roles->isNotEmpty())
        <details @if($selectedRoleIds->isNotEmpty()) open @endif>
            <summary>Select roles <small>(choose one or more)</small></summary>
            <div class="admin-role-options">
                @foreach($roles as $role)<label><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($selectedRoleIds->contains($role->id))><span><strong>{{ $role->name }}</strong><small>{{ $role->permissions->count() }} permissions</small></span></label>@endforeach
            </div>
        </details>
        <p class="admin-help">A user needs a role with access-admin permission to enter the workspace.</p>
    @else
        <p>No roles are available to assign. @can('route-admin-roles-create')<a href="{{ route('admin.roles.create') }}">Create a role</a> first.@endcan</p>
    @endif
</fieldset>
