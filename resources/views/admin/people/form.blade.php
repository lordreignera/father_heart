<x-admin-layout :title="$person->exists ? 'Edit person' : 'Register person'">
    <div class="admin-content admin-form-content"><a class="admin-back" href="{{ $person->exists ? route('admin.people.show', $person) : route('admin.people.index') }}">← People registry</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Care records</p><h1>{{ $person->exists ? 'Edit person' : 'Register a person' }}</h1><p>Only the basic identity is recorded here. The full intake questionnaire follows as an assessment.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form class="admin-form-card" method="POST" action="{{ $person->exists ? route('admin.people.update', $person) : route('admin.people.store') }}">@csrf @if($person->exists) @method('PUT') @endif
            <div class="admin-field-grid"><label>Full name<input name="full_name" value="{{ old('full_name', $person->full_name) }}" required maxlength="180"></label><label>Phone number<input name="phone" value="{{ old('phone', $person->phone) }}" maxlength="40" inputmode="tel"></label><label>NIN <small>(optional, stored encrypted)</small><input name="nin" value="" maxlength="30" autocomplete="off" placeholder="{{ $person->exists ? 'Leave blank to keep existing NIN' : '' }}"></label></div>
            @include('admin.people._duplicate-review')
            <div class="admin-form-actions"><button class="admin-primary-link" type="submit">Save person</button></div>
        </form>
    </div>
</x-admin-layout>
