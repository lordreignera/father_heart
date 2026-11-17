<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyChild;
use App\Models\TherapyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TherapyDocumentController extends Controller
{
    public function store(Request $request, TherapyChild $child)
    {
        abort_unless($request->user()->can('route-admin-therapy-children-show'), 403);
        $data = $request->validate(['category' => ['required', Rule::in(['medical_report', 'education_plan', 'therapy_assessment', 'signed_consent', 'other'])],
            'document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')]]);
        $file = $data['document'];
        $path = $file->store('therapy-documents/'.$child->id, 'local');
        if (! $path) throw new \RuntimeException('The document could not be saved.');
        try {
            $child->documents()->create(['category' => $data['category'], 'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255), 'path' => $path, 'uploaded_by' => $request->user()->id]);
        } catch (\Throwable $exception) { Storage::disk('local')->delete($path); throw $exception; }
        return redirect()->route('admin.therapy.children.show', $child)->with('status', 'Document stored privately.');
    }
    public function download(TherapyDocument $document)
    {
        abort_unless(auth()->user()->can('route-admin-therapy-children-show'), 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, null, ['Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff']);
    }
}
