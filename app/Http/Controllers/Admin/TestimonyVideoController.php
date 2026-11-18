<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestimonyVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class TestimonyVideoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $videos = TestimonyVideo::with('uploader')->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))->latest()->paginate(20)->withQueryString();
        return response()->view('admin.testimonies.index', compact('videos'))->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('route-admin-testimonies-index'), 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:2000'], 'video' => ['required', File::types(['mp4', 'webm', 'mov'])->max('100mb')]]);
        $file = $data['video'];
        $mime = $file->getMimeType();
        if (! in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['video' => 'Upload a valid MP4, WebM or MOV video.']);
        }
        $path = $file->store('testimony-videos', 'local');
        if (! $path) throw new \RuntimeException('The video could not be saved.');
        try {
            TestimonyVideo::create(['title' => $data['title'], 'description' => $data['description'] ?? null, 'file_path' => $path, 'mime_type' => $mime, 'file_size' => $file->getSize(), 'uploaded_by' => $request->user()->id]);
        } catch (\Throwable $exception) { Storage::disk('local')->delete($path); throw $exception; }
        return redirect()->route('admin.testimonies.index')->with('status', 'Testimony saved privately. It is not approved for public sharing.');
    }

    public function show(Request $request, TestimonyVideo $testimony)
    {
        abort_unless($request->user()->can('route-admin-testimonies-index'), 403);
        abort_unless(Storage::disk('local')->exists($testimony->file_path), 404);
        $response = response()->file(Storage::disk('local')->path($testimony->file_path), [
            'Content-Type' => $testimony->mime_type, 'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
        $response->setPrivate();
        return $response;
    }
}
