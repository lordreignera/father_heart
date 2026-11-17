<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentPhoto;
use App\Models\District;
use App\Models\DistributionEvent;
use App\Models\Person;
use App\Models\MediaConsent;
use App\Support\IntakeCodeGenerator;
use App\Support\LegacyMedia;
use App\Support\AdminRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $assessments = Assessment::query()->with(['person', 'distributionEvent', 'district'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.assessments.index', compact('assessments'));
    }

    public function create(Request $request): View
    {
        $person = Person::findOrFail($request->integer('person_id'));

        return $this->form(new Assessment(['person_id' => $person->id]), $person);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $consent = $this->validatedConsent($request, $data['age_years'] ?? null);
        $data['assessed_by'] = $request->user()->id;
        $assessment = $this->saveWithPhotos($request, $data, null, $consent);

        return AdminRedirect::firstAllowed(['admin.assessments.show' => [$assessment], 'admin.assessments.index' => []])->with('status', 'Assessment saved. A selected device is not counted as a handover.');
    }

    public function show(Assessment $assessment): View
    {
        $assessment->load(['person', 'district', 'subcounty', 'distributionEvent', 'mediaConsents', 'photos', 'legacySource']);
        $legacyPhotos = [];
        if ($assessment->legacySource && config('legacy.media_ready')) {
            $original = $assessment->legacySource->raw_payload;
            foreach (['before' => 'beforee', 'after' => 'afterr'] as $kind => $field) {
                $legacyPhotos[$kind] = app(LegacyMedia::class)->imagePath($original[$field] ?? null) !== null;
            }
        }
        return view('admin.assessments.show', compact('assessment', 'legacyPhotos'));
    }

    public function edit(Assessment $assessment): View
    {
        return $this->form($assessment, $assessment->person);
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $data = $this->validated($request, $assessment);
        if ((int) $data['person_id'] !== $assessment->person_id) {
            throw ValidationException::withMessages(['person_id' => 'The person on an existing assessment cannot be changed.']);
        }
        $consent = $this->validatedConsent($request, $data['age_years'] ?? null);
        $this->saveWithPhotos($request, $data, $assessment, $consent);

        return AdminRedirect::firstAllowed(['admin.assessments.show' => [$assessment], 'admin.assessments.index' => []])->with('status', 'Assessment updated.');
    }

    public function photo(AssessmentPhoto $photo)
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);
        return Storage::disk('local')->response($photo->path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function legacyPhoto(Assessment $assessment, string $kind, LegacyMedia $media)
    {
        abort_unless(in_array($kind, ['before', 'after'], true), 404);
        $original = $assessment->legacySource;
        abort_unless($original, 404);
        $payload = $original->raw_payload;
        $path = $media->imagePath($payload[$kind === 'before' ? 'beforee' : 'afterr'] ?? null);
        abort_unless($path, 404);
        $info = getimagesize($path);
        return response()->file($path, [
            'Content-Type' => $info['mime'], 'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    private function validatedConsent(Request $request, mixed $age): ?array
    {
        $input = $request->input('consent', []);
        if (!is_array($input)) throw ValidationException::withMessages(['consent' => 'Invalid consent details.']);
        $hasDetails = collect($input)->contains(fn ($value) => filled($value)) || $request->hasFile('consent.document');
        if (!$hasDetails) return null;
        abort_unless($request->user()->can('route-admin-assessments-consents-store'), 403);
        $validated = $request->validate([
            'consent.status' => ['required', Rule::in(['granted', 'declined'])],
            'consent.signer_name' => ['nullable', 'string', 'max:180'],
            'consent.recipient_identity' => ['nullable', 'string', 'max:30'],
            'consent.recipient_phone' => ['nullable', 'string', 'max:40'],
            'consent.signed_on' => ['nullable', 'date'],
            'consent.guardian_name' => ['nullable', 'string', 'max:180'],
            'consent.guardian_signed_on' => ['nullable', 'date'],
            'consent.document' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ])['consent'];
        if ($validated['status'] === 'granted') {
            if ($age === null) throw ValidationException::withMessages(['consent.status' => 'Record the age at assessment before granting consent.']);
            $rules = ['consent.signer_name' => ['required', 'string', 'max:180'], 'consent.signed_on' => ['required', 'date'], 'consent.document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')]];
            if ((int) $age < 18) $rules += ['consent.guardian_name' => ['required', 'string', 'max:180'], 'consent.guardian_signed_on' => ['required', 'date']];
            $request->validate($rules);
        }
        unset($validated['document']);
        return $validated;
    }

    private function saveWithPhotos(Request $request, array $data, ?Assessment $assessment = null, ?array $consent = null): Assessment
    {
        $stored = [];
        foreach (['before', 'after'] as $kind) {
            $file = $request->file($kind.'_camera') ?: $request->file($kind.'_upload');
            if ($file) {
                try {
                    $path = $file->store('assessment-photos/'.$data['person_id'], 'local');
                    if (! $path) throw new \RuntimeException('Could not save the photo.');
                    $stored[$kind] = $path;
                } catch (\Throwable $exception) {
                    foreach ($stored as $path) Storage::disk('local')->delete($path);
                    throw $exception;
                }
            }
        }
        unset($data['before_camera'], $data['before_upload'], $data['after_camera'], $data['after_upload']);

        try {
            if ($consent && $request->hasFile('consent.document')) {
                $path = $request->file('consent.document')->store('consents/'.$data['person_id'], 'local');
                if (! $path) throw new \RuntimeException('Could not save the signed consent form.');
                $stored['consent'] = $path;
                $consent['document_path'] = $path;
            }
            return DB::transaction(function () use ($assessment, $data, $stored, $request, $consent): Assessment {
                if ($assessment) $assessment->update($data);
                else {
                    $person = Person::findOrFail($data['person_id']);
                    $data['intake_code'] = app(IntakeCodeGenerator::class)->generate($person, $data['assessed_on'] ?? null);
                    $assessment = Assessment::create($data);
                }
                foreach ($stored as $kind => $path) {
                    if ($kind === 'consent') continue;
                    $assessment->photos()->create(['kind' => $kind, 'path' => $path, 'uploaded_by' => $request->user()->id]);
                }
                if ($consent) MediaConsent::create($consent + ['person_id' => $assessment->person_id, 'assessment_id' => $assessment->id]);
                return $assessment;
            });
        } catch (\Throwable $exception) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    private function form(Assessment $assessment, Person $person): View
    {
        return view('admin.assessments.form', [
            'assessment' => $assessment,
            'person' => $person,
            'districts' => District::where('is_active', true)->orderBy('name')->get(),
            'events' => DistributionEvent::orderByDesc('event_date')->get(),
        ]);
    }

    private function validated(Request $request, ?Assessment $assessment = null): array
    {
        $genders = ['male', 'female'];
        if ($assessment && in_array($assessment->gender, ['other', 'not_recorded'], true)) $genders[] = $assessment->gender;
        $data = $request->validate([
            'person_id' => ['required', Rule::exists('people', 'id')->whereNull('deleted_at')],
            'distribution_event_id' => ['nullable', 'exists:distribution_events,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'subcounty_id' => ['nullable', 'exists:subcounties,id'],
            'assessed_on' => ['nullable', 'date'],
            'age_years' => ['nullable', 'integer', 'between:0,120'],
            'gender' => ['nullable', Rule::in($genders)],
            'wearing' => ['nullable', 'string', 'max:180'],
            'device_requested' => ['nullable', 'string', 'max:80'],
            'device_size' => ['nullable', 'string', 'max:40'],
            'not_given_reason' => ['nullable', 'string'],
            'back_measurement' => ['nullable', 'string', 'max:40'],
            'thigh_measurement' => ['nullable', 'string', 'max:40'],
            'leg_measurement' => ['nullable', 'string', 'max:40'],
            'hips_width' => ['nullable', 'string', 'max:40'],
            'disability_story' => ['nullable', 'string'],
            'causes' => ['nullable', 'array'], 'causes.*' => ['string', Rule::in(config('assessment.causes'))],
            'diagnoses' => ['nullable', 'array'], 'diagnoses.*' => ['string', Rule::in(config('assessment.diagnoses'))],
            'cause_other' => ['nullable', 'string', 'max:180'],
            'diagnosis_other' => ['nullable', 'string', 'max:180'],
            'current_mobility' => ['nullable', 'string'],
            'has_wheelchair' => ['nullable', 'boolean'],
            'has_wheelchair_explanation' => ['nullable', 'string'],
            'ever_had_wheelchair' => ['nullable', 'boolean'],
            'ever_had_wheelchair_explanation' => ['nullable', 'string'],
            'mobility_hopes' => ['nullable', 'string'],
            'conversation_notes' => ['nullable', 'string'],
            'thanksgiving_message' => ['nullable', 'string'],
            'other_notes' => ['nullable', 'string'],
            'salvation_status' => ['nullable', Rule::in(config('assessment.salvation_statuses'))],
            'church_referral' => ['nullable', 'string', 'max:180'],
            'staff_volunteer_names' => ['nullable', 'string', 'max:255'],
            'registration_complete' => ['required', 'boolean'],
            'assessment_complete' => ['required', 'boolean'],
            'safety_lesson_received' => ['required', 'boolean'],
            'therapy_received' => ['required', 'boolean'],
            'evangelism_notes' => ['nullable', 'string'],
            'before_camera' => ['nullable', 'prohibits:before_upload', File::image()->max('10mb')],
            'before_upload' => ['nullable', 'prohibits:before_camera', File::image()->max('10mb')],
            'after_camera' => ['nullable', 'prohibits:after_upload', File::image()->max('10mb')],
            'after_upload' => ['nullable', 'prohibits:after_camera', File::image()->max('10mb')],
            'released_to_go' => ['required', 'boolean'],
            'status' => ['required', 'in:draft,assessed,waitlisted,complete,legacy_unverified'],
        ]);

        if (filled($data['subcounty_id'] ?? null)) {
            $matches = DB::table('subcounties')->where('id', $data['subcounty_id'])
                ->where('district_id', $data['district_id'] ?? 0)->exists();
            if (! $matches) throw ValidationException::withMessages(['subcounty_id' => 'Choose a subcounty in the selected district.']);
        }

        $data['causes'] = $data['causes'] ?? [];
        $data['diagnoses'] = $data['diagnoses'] ?? [];

        return $data;
    }
}
