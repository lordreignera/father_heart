<x-admin-layout :title="$staff->exists ? 'Edit staff account' : 'Add staff account'">
    <div class="admin-content admin-form-content">
        <a class="admin-back" href="{{ $staff->exists ? route('admin.users.show', $staff) : route('admin.users.index') }}">Back to staff accounts</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Workspace access</p><h1>{{ $staff->exists ? 'Edit staff account' : 'Add staff account' }}</h1><p>Assign roles to control which areas this staff member can use.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $staff->exists ? route('admin.users.update', $staff) : route('admin.users.store') }}">
            @csrf @if($staff->exists) @method('PUT') @endif
            <div class="admin-field-grid">
                <label>Full name<input name="name" value="{{ old('name', $staff->name) }}" required maxlength="255" autocomplete="name"></label>
                <label>Email address<input type="email" name="email" value="{{ old('email', $staff->email) }}" required maxlength="255" autocomplete="email"></label>
                <label>Password <small>{{ $staff->exists ? '(leave blank to keep current password)' : '(at least 12 characters)' }}</small><input type="password" name="password" {{ $staff->exists ? '' : 'required' }} minlength="12" autocomplete="new-password"></label>
                <label>Confirm password<input type="password" name="password_confirmation" {{ $staff->exists ? '' : 'required' }} minlength="12" autocomplete="new-password"></label>
            </div>
            @include('admin.users._role-dropdown', ['selectedRoleIds' => old('roles', $staff->exists ? $staff->roles->modelKeys() : [])])
            <p class="admin-help">Passwords are stored hashed. Super administrator access is restricted to existing super administrators.</p>
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">{{ $staff->exists ? 'Save changes' : 'Create staff account' }}</button></div>
        </form>
    </div>
</x-admin-layout>
