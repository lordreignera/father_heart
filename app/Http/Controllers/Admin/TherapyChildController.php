<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Person;
use App\Models\TherapyChild;
use App\Models\TherapyService;
use App\Support\TherapyCatalogues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TherapyChildController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $children = TherapyChild::with(['person', 'district'])->withCount('visits')
            ->when($data['q'] ?? null, fn ($q, $term) => $q->whereHas('person', fn ($p) => $p->where('full_name', 'like', '%'.$term.'%')))
            ->latest()->paginate(25)->withQueryString();
        return view('admin.therapy.children-index', compact('children'));
    }

    public function create(Request $request)
    {
        $term = $request->validate(['person_search' => ['nullable', 'string', 'max:120']])['person_search'] ?? '';
        $people = collect();
        if (mb_strlen($term) >= 2 && $request->user()->can('route-admin-people-index')) {
            $people = Person::where(fn ($q) => $q->where('full_name', 'like', '%'.$term.'%')->orWhere('phone', 'like', '%'.$term.'%'))->orderBy('full_name')->limit(30)->get(['id', 'full_name', 'phone']);
        }
        if ($request->user()->can('route-admin-people-index') && old('person_id') && ! $people->contains('id', old('person_id'))) {
            $selected = Person::find(old('person_id'), ['id', 'full_name', 'phone']);
            if ($selected) $people->push($selected);
        }
        return view('admin.therapy.child-form', ['child' => new TherapyChild(['registered_on' => now()]), 'districts' => District::where('is_active', true)->orderBy('name')->get(), 'people' => $people, 'personSearch' => $term, 'options' => TherapyCatalogues::activeGrouped()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('route-admin-people-index'), 403, 'Permission to search the people registry is required to link a child.');
        $newPerson = ! $request->filled('person_id') && ($request->filled('first_name') || $request->filled('last_name'));
        if ($newPerson) abort_unless($request->user()->can('route-admin-people-store'), 403, 'Permission to register people is required to add a child.');
        if ($request->filled('person_id') && ($request->filled('first_name') || $request->filled('last_name'))) {
            throw ValidationException::withMessages(['person_id' => 'Select an existing child or enter a new name, not both.']);
        }
        $data = $this->validated($request);
        $name = null;
        if ($newPerson) {
            $name = trim($data['first_name']).' '.trim($data['last_name']);
            if (mb_strlen($name) > 180) throw ValidationException::withMessages(['last_name' => 'The full name must not exceed 180 characters.']);
        }
        $child = DB::transaction(function () use ($request, $data, $newPerson, $name) {
            if ($newPerson) $data['person_id'] = Person::create(['full_name' => $name, 'created_by' => $request->user()->id])->id;
            unset($data['first_name'], $data['last_name']);
            $data['created_by'] = $request->user()->id;
            return TherapyChild::create($data);
        });
        return redirect()->route('admin.therapy.children.show', $child)->with('status', 'Child registered for therapy.');
    }

    public function show(TherapyChild $child)
    {
        $child->load(['person', 'district']);
        $visits = auth()->user()->can('route-admin-therapy-visits-show') ? $child->visits()->with('services')->latest('visited_on')->paginate(25) : null;
        $services = auth()->user()->can('route-admin-therapy-visits-store') ? TherapyService::where('is_active', true)->orderBy('category')->orderBy('name')->get() : collect();
        $documents = auth()->user()->can('route-admin-therapy-documents-download') ? $child->documents()->latest()->get() : collect();
        return view('admin.therapy.child-show', compact('child', 'visits', 'services', 'documents'));
    }

    public function edit(TherapyChild $child)
    {
        $child->load('person');
        return view('admin.therapy.child-form', ['child' => $child, 'districts' => District::where('is_active', true)->orWhere('id', $child->district_id)->orderBy('name')->get(), 'people' => collect(), 'personSearch' => '', 'options' => TherapyCatalogues::activeGrouped()]);
    }

    public function update(Request $request, TherapyChild $child)
    {
        $data = $this->validated($request, $child);
        unset($data['person_id']);
        $child->update($data);
        return redirect()->route('admin.therapy.children.show', $child)->with('status', 'Therapy registration updated.');
    }

    private function validated(Request $request, ?TherapyChild $child = null): array
    {
        $custom = $request->validate([
            'registration_custom' => ['nullable', 'array:previous_therapy,musical_interests,session_type,frequency'],
            'registration_custom.previous_therapy' => ['nullable', 'string', 'max:1000'],
            'registration_custom.musical_interests' => ['nullable', 'string', 'max:1000'],
            'registration_custom.session_type' => ['nullable', 'string', 'max:120'],
            'registration_custom.frequency' => ['nullable', 'string', 'max:120'],
        ])['registration_custom'] ?? [];
        $profile = $request->input('registration_data');
        if (is_array($profile)) {
            foreach ($custom as $key => $value) if (filled($value)) $profile[$key] = trim($value);
            $request->merge(['registration_data' => $profile]);
        }
        $newPerson = ! $child && ! $request->filled('person_id') && ($request->filled('first_name') || $request->filled('last_name'));
        $genders = ['male', 'female'];
        $existingGender = $child?->registration_data['gender'] ?? null;
        if (in_array($existingGender, ['other', 'not_recorded'], true)) $genders[] = $existingGender;
        $rules = [
            'person_id' => $newPerson ? ['exclude'] : ['required', 'integer', Rule::exists('people', 'id')->whereNull('deleted_at'), Rule::unique('therapy_children', 'person_id')->ignore($child?->id)],
            'first_name' => [$newPerson ? 'required' : 'nullable', 'string', 'max:90'],
            'last_name' => [$newPerson ? 'required' : 'nullable', 'string', 'max:90'],
            'registered_on' => ['required', 'date'], 'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'registration_data' => ['required', 'array:gender,nationality,religion,address,blood_group,file_number,disabilities,disabilities_selected,disabilities_other,diagnosis_date,diagnosing_provider,medications,allergies,medical_conditions,mobility,communication,previous_therapy,musical_interests,session_type,preferred_days,preferred_time,frequency,guardian_goals,therapist_notes,siblings,guardians,emergency,nearest_facility,doctor_contact,consent_participation,consent_emergency,consent_internal_media,consent_external_media,toilet_independent,declaration_accuracy,declaration_notify_changes,guardian_signer,signed_on,witness'],
            'registration_data.gender' => ['nullable', Rule::in($genders)],
            'registration_data.nationality' => ['nullable', 'string', 'max:120'], 'registration_data.religion' => ['nullable', 'string', 'max:120'],
            'registration_data.address' => ['nullable', 'string', 'max:500'], 'registration_data.blood_group' => ['nullable', 'string', 'max:40'],
            'registration_data.file_number' => ['nullable', 'string', 'max:120'],
            'registration_data.disabilities' => ['nullable', 'string', 'max:1000'], 'registration_data.diagnosis_date' => ['nullable', 'date'],
            'registration_data.disabilities_selected' => ['nullable', 'array', 'max:100'],
            'registration_data.disabilities_selected.*' => ['string', 'distinct', 'max:180', Rule::in(TherapyCatalogues::active('disabilities')->pluck('name')->merge($child?->registration_data['disabilities_selected'] ?? [])->all())],
            'registration_data.disabilities_other' => ['nullable', 'string', 'max:1000'],
            'registration_data.diagnosing_provider' => ['nullable', 'string', 'max:255'], 'registration_data.medications' => ['nullable', 'string', 'max:3000'],
            'registration_data.allergies' => ['nullable', 'string', 'max:3000'], 'registration_data.medical_conditions' => ['nullable', 'string', 'max:3000'],
            'registration_data.mobility' => ['nullable', 'string', 'max:500'], 'registration_data.communication' => ['nullable', 'string', 'max:500'],
            'registration_data.previous_therapy' => ['nullable', 'string', 'max:1000'], 'registration_data.musical_interests' => ['nullable', 'string', 'max:1000'],
            'registration_data.session_type' => ['nullable', 'string', 'max:120'], 'registration_data.preferred_days' => ['nullable', 'string', 'max:255'],
            'registration_data.preferred_time' => ['nullable', 'string', 'max:80'], 'registration_data.frequency' => ['nullable', 'string', 'max:120'],
            'registration_data.guardian_goals' => ['nullable', 'string', 'max:5000'], 'registration_data.therapist_notes' => ['nullable', 'string', 'max:5000'],
            'registration_data.siblings' => ['nullable', 'array', 'max:10'], 'registration_data.siblings.*' => ['array:name,birth_date,school'], 'registration_data.siblings.*.name' => ['nullable', 'string', 'max:180'],
            'registration_data.siblings.*.birth_date' => ['nullable', 'date'], 'registration_data.siblings.*.school' => ['nullable', 'string', 'max:180'],
            'registration_data.guardians' => ['nullable', 'array', 'max:2'], 'registration_data.guardians.*' => ['array:name,relationship,primary_phone,secondary_phone,email,occupation,employer,work_address,identity_number,identity_expiry'], 'registration_data.guardians.*.name' => ['nullable', 'string', 'max:180'],
            'registration_data.guardians.*.relationship' => ['nullable', 'string', 'max:120'], 'registration_data.guardians.*.primary_phone' => ['nullable', 'string', 'max:40'],
            'registration_data.guardians.*.secondary_phone' => ['nullable', 'string', 'max:40'], 'registration_data.guardians.*.email' => ['nullable', 'email', 'max:191'],
            'registration_data.guardians.*.occupation' => ['nullable', 'string', 'max:180'], 'registration_data.guardians.*.employer' => ['nullable', 'string', 'max:180'],
            'registration_data.guardians.*.work_address' => ['nullable', 'string', 'max:500'], 'registration_data.guardians.*.identity_number' => ['nullable', 'string', 'max:120'],
            'registration_data.guardians.*.identity_expiry' => ['nullable', 'date'],
            'registration_data.emergency' => ['nullable', 'array', 'max:2'], 'registration_data.emergency.*' => ['array:name,relationship,phone,address'], 'registration_data.emergency.*.name' => ['nullable', 'string', 'max:180'],
            'registration_data.emergency.*.relationship' => ['nullable', 'string', 'max:120'], 'registration_data.emergency.*.phone' => ['nullable', 'string', 'max:40'],
            'registration_data.emergency.*.address' => ['nullable', 'string', 'max:500'], 'registration_data.nearest_facility' => ['nullable', 'string', 'max:255'],
            'registration_data.doctor_contact' => ['nullable', 'string', 'max:255'],
            'registration_data.consent_participation' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.consent_emergency' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.consent_internal_media' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.consent_external_media' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.toilet_independent' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.declaration_accuracy' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.declaration_notify_changes' => ['nullable', Rule::in(['yes', 'no', 'not_recorded'])],
            'registration_data.guardian_signer' => ['nullable', 'string', 'max:180'], 'registration_data.signed_on' => ['nullable', 'date'],
            'registration_data.witness' => ['nullable', 'string', 'max:180'],
        ];
        $data = $request->validate($rules);
        if ($child && (int) $data['person_id'] !== $child->person_id) throw ValidationException::withMessages(['person_id' => 'The linked person cannot be changed here.']);
        return $data;
    }
}
