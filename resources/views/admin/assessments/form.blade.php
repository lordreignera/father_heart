<x-admin-layout :title="$assessment->exists ? 'Edit assessment' : 'New assessment'">
    <div class="admin-content admin-assessment-content">
        <a class="admin-back" href="{{ $assessment->exists ? route('admin.assessments.show', $assessment) : route('admin.people.show', $person) }}">← {{ $person->full_name }}</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Recipient intake questionnaire</p><h1>{{ $assessment->exists ? 'Edit assessment' : 'New assessment' }}</h1><p>Record the paper-form answers as given. Do not infer a handover or photo consent from this assessment.</p></div></div>
        @if($errors->any()) <div class="admin-error" role="alert"><strong>Please check the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form method="POST" enctype="multipart/form-data" data-assessment-form data-has-errors="{{ $errors->any() ? 'true' : 'false' }}" action="{{ $assessment->exists ? route('admin.assessments.update', $assessment) : route('admin.assessments.store') }}">
            @csrf @if($assessment->exists) @method('PUT') @endif
            <input type="hidden" name="person_id" value="{{ $person->id }}">
            <div class="assessment-guide" data-assessment-guide hidden>
                <div class="assessment-guide-top"><div><p class="admin-eyebrow">Assessment for {{ $person->full_name }}</p><strong data-assessment-progress aria-live="polite">Step 1 of 6</strong><p>Move between sections without losing your entries. Save when you are ready.</p></div><div class="assessment-view-toggle" role="group" aria-label="Form view"><button type="button" data-assessment-mode="steps" aria-pressed="true">Step by step</button><button type="button" data-assessment-mode="full" aria-pressed="false">Full form</button></div></div>
                <nav class="assessment-step-list" aria-label="Assessment sections">
                    @foreach(['Intake', 'Mobility', 'Disability', 'Personal story', 'Checklist', 'Consent'] as $label)
                        <button type="button" data-assessment-go="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $label }}</button>
                    @endforeach
                </nav>
            </div>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>1. Intake details</h2><p class="admin-help">Person: <strong>{{ $person->full_name }}</strong> · Phone: {{ $person->phone ?: 'not recorded' }}</p>
                <div class="admin-field-grid">
                    <label>Intake code<input value="{{ $assessment->intake_code ?: 'Generated when saved' }}" readonly><small>Assigned automatically using the assessment date.</small></label>
                    <label>Assessment date<input type="date" name="assessed_on" value="{{ old('assessed_on', $assessment->exists ? $assessment->assessed_on?->format('Y-m-d') : now()->format('Y-m-d')) }}"></label>
                    <label>Distribution event<select name="distribution_event_id"><option value="">No event selected</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected(old('distribution_event_id', $assessment->distribution_event_id) == $event->id)>{{ $event->name }}</option>@endforeach</select></label>
                    <label>District or city<select id="assessment-district" name="district_id" data-searchable-district data-location-district="assessment-subcounty" data-subcounty-url="{{ route('admin.location-options.subcounties') }}"><option value="">Select district</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected(old('district_id', $assessment->district_id) == $district->id)>{{ $district->name }}{{ $district->kind === 'city' ? ' City' : '' }}</option>@endforeach</select></label>
                    <label>Subcounty / town council / division<select id="assessment-subcounty" name="subcounty_id" data-selected="{{ old('subcounty_id', $assessment->subcounty_id) }}"><option value="">Select district first</option>@if(old('subcounty_id', $assessment->subcounty_id))<option value="{{ old('subcounty_id', $assessment->subcounty_id) }}" selected>Previously selected local unit</option>@endif</select></label>
                    <label>Age at assessment<input type="number" name="age_years" min="0" max="120" value="{{ old('age_years', $assessment->age_years) }}"></label>
                    <label>Gender recorded<select name="gender"><option value="">Not recorded</option>@if(in_array($assessment->gender, ['other', 'not_recorded'], true))<option value="{{ $assessment->gender }}" @selected(old('gender', $assessment->gender) === $assessment->gender)>Previously recorded value (unchanged)</option>@endif @foreach(['male' => 'Male', 'female' => 'Female'] as $value => $label)<option value="{{ $value }}" @selected(old('gender', $assessment->gender) === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Wearing / current equipment<input name="wearing" value="{{ old('wearing', $assessment->wearing) }}" maxlength="180"></label>
                </div>
            </section>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>2. Mobility need & measurements</h2>
                <div class="admin-field-grid"><label>Mobility device considered<select name="device_requested"><option value="">Not selected</option>@foreach(config('assessment.devices') as $device)<option value="{{ $device }}" @selected(old('device_requested', $assessment->device_requested) === $device)>{{ $device }}</option>@endforeach</select></label>
                    <label>Wheelchair size / generation<select name="device_size"><option value="">Not selected</option>@foreach(config('assessment.sizes') as $size)<option value="{{ $size }}" @selected(old('device_size', $assessment->device_size) === $size)>{{ $size }}</option>@endforeach</select></label>
                    <label>Back measurement<input name="back_measurement" value="{{ old('back_measurement', $assessment->back_measurement) }}" maxlength="40"></label>
                    <label>Thigh measurement<input name="thigh_measurement" value="{{ old('thigh_measurement', $assessment->thigh_measurement) }}" maxlength="40"></label>
                    <label>Leg measurement<input name="leg_measurement" value="{{ old('leg_measurement', $assessment->leg_measurement) }}" maxlength="40"></label>
                    <label>Hips width<input name="hips_width" value="{{ old('hips_width', $assessment->hips_width) }}" maxlength="40"></label>
                </div>
                <label class="admin-wide-label">If no device was given, explain<textarea name="not_given_reason" rows="2">{{ old('not_given_reason', $assessment->not_given_reason) }}</textarea></label>
            </section>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>3. Disability and mobility</h2>
                <label class="admin-wide-label">Disability story — when and how acquired<textarea name="disability_story" rows="4">{{ old('disability_story', $assessment->disability_story) }}</textarea></label>
                <div class="admin-choice-columns"><fieldset><legend>Cause of disability</legend>@foreach(config('assessment.causes') as $cause)<label><input type="checkbox" name="causes[]" value="{{ $cause }}" @checked(in_array($cause, old('causes', $assessment->causes ?? [])))> {{ $cause }}</label>@endforeach</fieldset><fieldset><legend>Disability / diagnosis</legend>@foreach(config('assessment.diagnoses') as $diagnosis)<label><input type="checkbox" name="diagnoses[]" value="{{ $diagnosis }}" @checked(in_array($diagnosis, old('diagnoses', $assessment->diagnoses ?? [])))> {{ $diagnosis }}</label>@endforeach</fieldset></div>
                <div class="admin-field-grid"><label>Other cause<input name="cause_other" value="{{ old('cause_other', $assessment->cause_other) }}"></label><label>Other diagnosis<input name="diagnosis_other" value="{{ old('diagnosis_other', $assessment->diagnosis_other) }}"></label></div>
                <label class="admin-wide-label">Current method of mobility<textarea name="current_mobility" rows="2" placeholder="Crawls, carried, crutches, other...">{{ old('current_mobility', $assessment->current_mobility) }}</textarea></label>
                <div class="admin-field-grid"><label>Already has a wheelchair?<select name="has_wheelchair"><option value="">Not recorded</option><option value="1" @selected(old('has_wheelchair', $assessment->has_wheelchair) === true || old('has_wheelchair', $assessment->has_wheelchair) === '1')>Yes</option><option value="0" @selected(old('has_wheelchair', $assessment->has_wheelchair) === false || old('has_wheelchair', $assessment->has_wheelchair) === '0')>No</option></select></label><label>Ever had a wheelchair?<select name="ever_had_wheelchair"><option value="">Not recorded</option><option value="1" @selected(old('ever_had_wheelchair', $assessment->ever_had_wheelchair) === true || old('ever_had_wheelchair', $assessment->ever_had_wheelchair) === '1')>Yes</option><option value="0" @selected(old('ever_had_wheelchair', $assessment->ever_had_wheelchair) === false || old('ever_had_wheelchair', $assessment->ever_had_wheelchair) === '0')>No</option></select></label></div>
                <label class="admin-wide-label">If already has one, explain<textarea name="has_wheelchair_explanation" rows="2">{{ old('has_wheelchair_explanation', $assessment->has_wheelchair_explanation) }}</textarea></label>
                <label class="admin-wide-label">If ever had one, explain<textarea name="ever_had_wheelchair_explanation" rows="2">{{ old('ever_had_wheelchair_explanation', $assessment->ever_had_wheelchair_explanation) }}</textarea></label>
            </section>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>4. Personal story and hopes</h2>
                <label class="admin-wide-label">Why is mobility support wanted? How might life change?<textarea name="mobility_hopes" rows="3">{{ old('mobility_hopes', $assessment->mobility_hopes) }}</textarea></label>
                <label class="admin-wide-label">Conversation story / family, home, work or school notes<textarea name="conversation_notes" rows="4">{{ old('conversation_notes', $assessment->conversation_notes) }}</textarea></label>
                <label class="admin-wide-label">Thanksgiving message, in their own words<textarea name="thanksgiving_message" rows="3">{{ old('thanksgiving_message', $assessment->thanksgiving_message) }}</textarea></label>
                <label class="admin-wide-label">Other notes, referrals or follow-up<textarea name="other_notes" rows="3">{{ old('other_notes', $assessment->other_notes) }}</textarea></label>
            </section>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>5. Ministry & process checklist</h2>
                <div class="admin-field-grid"><label>Salvation response<select name="salvation_status"><option value="">Not recorded</option>@foreach(['already_saved' => 'Already saved', 'did_not_get_saved' => 'Did not get saved', 'got_saved' => 'Got saved', 'family_member_got_saved' => 'Family member got saved'] as $value => $label)<option value="{{ $value }}" @selected(old('salvation_status', $assessment->salvation_status) === $value)>{{ $label }}</option>@endforeach</select></label><label>Church referral<input name="church_referral" value="{{ old('church_referral', $assessment->church_referral) }}"></label><label>Staff / volunteer names<input name="staff_volunteer_names" value="{{ old('staff_volunteer_names', $assessment->staff_volunteer_names) }}"></label><label>Assessment status<select name="status" required>@foreach(['draft' => 'Draft', 'assessed' => 'Assessed', 'waitlisted' => 'Waiting list', 'complete' => 'Assessment complete', 'legacy_unverified' => 'Historical — needs review'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $assessment->status ?: 'draft') === $value)>{{ $label }}</option>@endforeach</select></label></div>
                <div class="admin-checklist">@foreach(['registration_complete' => 'Registration', 'assessment_complete' => 'Assessment', 'safety_lesson_received' => 'Safety lesson received', 'therapy_received' => 'Therapy received', 'released_to_go' => 'Complete and released to go'] as $field => $label)<label><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $assessment->$field))> {{ $label }}</label>@endforeach</div>
                <label class="admin-wide-label">Evangelism notes<textarea name="evangelism_notes" rows="2">{{ old('evangelism_notes', $assessment->evangelism_notes) }}</textarea></label>
                <div class="admin-photo-grid">
                    @foreach(['before' => 'Before photo', 'after' => 'After photo'] as $kind => $label)
                        <fieldset class="admin-photo-upload"><legend>{{ $label }}</legend><p>Take a photo or choose an existing image, up to 10 MB.</p><label>Take photo<input type="file" name="{{ $kind }}_camera" accept="image/*" capture="environment"></label><label>Upload image<input type="file" name="{{ $kind }}_upload" accept="image/jpeg,image/png,image/webp,image/gif"></label></fieldset>
                    @endforeach
                </div>
                <p class="admin-help">Photos are stored privately. Select either camera or upload for each photo. Separate media consent is required before sharing publicly. If the form needs correcting, select photos again.</p>
            </section>
            <section class="admin-form-card admin-form-section" data-assessment-step><h2>6. Consent and release</h2>
                <p class="admin-help">The paper form covers photographs, video and audio for promotional materials, websites, social media and reports. It also includes no-compensation and liability-release terms. Explain the full signed form to the recipient or guardian; this summary is not a signature.</p>
                <p class="admin-help">Photos remain private unless valid consent is recorded. For a person under 18, the parent or legal guardian must also sign. Upload the signed paper form as evidence; typing a name does not replace a signature.</p>
                @can('route-admin-assessments-consents-store')
                @if($assessment->exists)<p class="admin-help">This section adds a new decision; it does not overwrite previous consent records. Leave it blank if there is no new decision.</p>@endif
                <div class="admin-field-grid">
                    <label>Consent decision<select name="consent[status]"><option value="">Not recorded / no new decision</option><option value="granted" @selected(old('consent.status') === 'granted')>Granted</option><option value="declined" @selected(old('consent.status') === 'declined')>Declined</option></select></label>
                    <label>Recipient name<input name="consent[signer_name]" maxlength="180" value="{{ old('consent.signer_name') }}"></label>
                    <label>Recipient NIN or identification number<input name="consent[recipient_identity]" maxlength="30" autocomplete="off"><small>Stored encrypted. Re-enter if the form needs correction.</small></label>
                    <label>Recipient phone number<input name="consent[recipient_phone]" maxlength="40" value="{{ old('consent.recipient_phone') }}"></label>
                    <label>Recipient signature date<input type="date" name="consent[signed_on]" value="{{ old('consent.signed_on') }}"></label>
                    <label>Parent / guardian name <small>Required for consent if under 18</small><input name="consent[guardian_name]" maxlength="180" value="{{ old('consent.guardian_name') }}"></label>
                    <label>Parent / guardian signature date<input type="date" name="consent[guardian_signed_on]" value="{{ old('consent.guardian_signed_on') }}"></label>
                    <label>Signed consent form <small>Required when granted; private PDF, JPG or PNG, up to 10 MB</small><input type="file" name="consent[document]" accept=".pdf,.jpg,.jpeg,.png"></label>
                </div>
                @else<p class="admin-help">You do not have permission to record a consent decision. An authorized staff member can record it separately.</p>@endcan
            </section>
            <div class="assessment-actions"><button type="button" class="admin-secondary-button" data-assessment-prev hidden>← Previous</button><span class="assessment-actions-spacer"></span><button type="button" class="admin-primary-link" data-assessment-next hidden>Next step →</button><button class="admin-primary-link" type="submit" data-assessment-save>Save assessment</button></div>
        </form>
    </div>
</x-admin-layout>
