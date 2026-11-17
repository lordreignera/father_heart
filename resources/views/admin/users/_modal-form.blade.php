@php($returning = old('_modal') === $modalId)
<dialog class="admin-dialog" id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" @if($returning) open @endif>
    <div class="admin-dialog-head"><div><p class="admin-eyebrow">Workspace access</p><h2 id="{{ $modalId }}-title">{{ $creating ? 'Add staff account' : 'Edit staff account' }}</h2><p>Assign roles to control which areas this staff member can use.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
    @if($returning && $errors->any())<div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="admin-dialog-form" method="POST" action="{{ $creating ? route('admin.users.store') : route('admin.users.update', $staff) }}">
        @csrf @unless($creating) @method('PUT') @endunless<input type="hidden" name="_modal" value="{{ $modalId }}">
        <div class="admin-field-grid">
            <label>Full name<input name="name" required maxlength="255" autocomplete="name" value="{{ $returning ? old('name') : $staff->name }}"></label>
            <label>Email address<input name="email" type="email" required maxlength="255" autocomplete="email" value="{{ $returning ? old('email') : $staff->email }}"></label>
            <label>{{ $creating ? 'Password' : 'New password (optional)' }}<small>{{ $creating ? 'At least 12 characters' : 'Leave blank to keep the existing password' }}</small><input name="password" type="password" minlength="12" autocomplete="new-password" @if($creating) required @endif></label>
            <label>Confirm password<input name="password_confirmation" type="password" minlength="12" autocomplete="new-password" @if($creating) required @endif></label>
        </div>
        @include('admin.users._role-dropdown', ['selectedRoleIds' => $returning ? old('roles', []) : ($creating ? [] : $staff->roles->modelKeys())])
        <div class="admin-dialog-actions"><button class="admin-secondary-button" type="button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">{{ $creating ? 'Create account' : 'Save changes' }}</button></div>
    </form>
</dialog>
