<x-admin-layout :title="$role->exists ? 'Edit role' : 'Create role'">
    <div class="admin-content admin-form-content"><a class="admin-back" href="{{ route('admin.roles.index') }}">Back to roles</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Workspace access</p><h1>{{ $role->exists ? 'Edit role' : 'Create role' }}</h1><p>Set the role name here, then manage its permissions from the roles table.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">@csrf @if($role->exists) @method('PUT') <input type="hidden" name="_role_name_only" value="1"> @endif
            <div class="admin-field-grid"><label>Role name<input name="name" value="{{ old('name', $role->name) }}" required maxlength="120" placeholder="For example, Intake Officer"></label></div>
            <p>Permissions are managed separately; renaming a role does not change them.</p>
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">{{ $role->exists ? 'Save role' : 'Create role' }}</button></div>
        </form>
    </div>
</x-admin-layout>
