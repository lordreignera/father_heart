@props(['person', 'modalId', 'creating' => false])
@php($returning = old('_modal') === $modalId)
<dialog class="admin-dialog" id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" @if($returning) open @endif>
    <div class="admin-dialog-head"><div><p class="admin-eyebrow">Care records</p><h2 id="{{ $modalId }}-title">{{ $creating ? 'Register a person' : 'Edit person details' }}</h2><p>Basic identity details for the people registry.</p></div><button type="button" class="admin-dialog-close" data-dialog-close aria-label="Close dialog">&times;</button></div>
    @if($returning && $errors->any()) <div class="admin-error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    <form class="admin-dialog-form" method="POST" action="{{ $creating ? route('admin.people.store') : route('admin.people.update', $person) }}">
        @csrf @unless($creating) @method('PUT') @endunless
        <input type="hidden" name="_modal" value="{{ $modalId }}">
        <div class="admin-field-grid"><label>Full name<input name="full_name" value="{{ $returning ? old('full_name') : $person->full_name }}" required maxlength="180" autocomplete="name"></label><label>Phone number<input name="phone" value="{{ $returning ? old('phone') : $person->phone }}" maxlength="40" inputmode="tel" autocomplete="tel"></label><label>NIN <small>(optional, stored encrypted)</small><input name="nin" value="{{ $returning ? old('nin') : '' }}" maxlength="30" autocomplete="off" placeholder="{{ $person->exists ? 'Leave blank to keep existing NIN' : '' }}"></label></div>
        @if($returning) @include('admin.people._duplicate-review') @endif
        <p class="admin-help">The intake questionnaire is recorded separately as an assessment.</p>
        <div class="admin-dialog-actions"><button type="button" class="admin-secondary-button" data-dialog-close>Cancel</button><button class="admin-primary-link" type="submit">{{ $creating ? 'Register person' : 'Save changes' }}</button></div>
    </form>
</dialog>
