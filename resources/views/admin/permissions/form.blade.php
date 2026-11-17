<x-admin-layout :title="$permission->exists ? 'Edit permission' : 'Add permission'">
    <div class="admin-content admin-form-content"><a class="admin-back" href="{{ route('admin.permissions.index') }}">Back to permission catalogue</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Workspace access</p><h1>{{ $permission->exists ? 'Edit permission' : 'Add permission' }}</h1><p>Use lowercase letters, numbers, and hyphens in a permission key.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $permission->exists ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">@csrf @if($permission->exists) @method('PUT') @endif
            <div class="admin-field-grid"><label>Permission key<input name="name" value="{{ old('name', $permission->name) }}" required maxlength="120" pattern="[a-z][a-z0-9-]*" placeholder="manage-volunteers"></label></div>
            <p class="admin-help">A new permission does not grant access by itself. Add it to a role, then assign that role to staff accounts.</p>
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">{{ $permission->exists ? 'Save permission' : 'Create permission' }}</button></div>
        </form>
    </div>
</x-admin-layout>
