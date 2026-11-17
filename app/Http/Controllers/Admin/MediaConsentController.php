<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\MediaConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaConsentController extends Controller
{
    public function store(Request $request, Assessment $assessment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['granted', 'declined', 'not_recorded'])],
            'signed_on' => ['nullable', 'date'],
            'signer_name' => ['nullable', 'string', 'max:180'],
            'guardian_name' => ['nullable', 'string', 'max:180'],
            'guardian_signed_on' => ['nullable', 'date'],
            'document' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ]);
        if ($data['status'] === 'granted') {
            if ($assessment->age_years === null) {
                throw ValidationException::withMessages(['status' => 'Record the person’s age before granting consent so guardian requirements can be checked.']);
            }
            $request->validate([
                'signed_on' => ['required', 'date'],
                'signer_name' => ['required', 'string', 'max:180'],
                'document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
            ]);
            if ($assessment->age_years !== null && $assessment->age_years < 18) {
                $request->validate([
                    'guardian_name' => ['required', 'string', 'max:180'],
                    'guardian_signed_on' => ['required', 'date'],
                ]);
            }
        }

        unset($data['document']);
        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('consents/'.$assessment->person_id, 'local');
        }
        try {
            MediaConsent::create($data + [
                'person_id' => $assessment->person_id,
                'assessment_id' => $assessment->id,
            ]);
        } catch (\Throwable $exception) {
            if (isset($data['document_path'])) Storage::disk('local')->delete($data['document_path']);
            throw $exception;
        }

        return back()->with('status', 'Consent record saved separately from the assessment.');
    }

    public function download(MediaConsent $mediaConsent): BinaryFileResponse
    {
        abort_unless($mediaConsent->document_path && Storage::disk('local')->exists($mediaConsent->document_path), 404);

        return response()->download(Storage::disk('local')->path($mediaConsent->document_path));
    }
}
