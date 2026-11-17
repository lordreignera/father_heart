<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TherapyServiceController extends Controller
{
    public function index() { return view('admin.therapy.services', ['services' => TherapyService::orderBy('category')->orderBy('name')->get()]); }
    public function store(Request $request) { TherapyService::create($this->validateData($request)); return back()->with('status', 'Service added.'); }
    public function update(Request $request, TherapyService $service) {
        $data = $this->validateData($request, $service);
        if ($service->visits()->exists() && ($service->name !== $data['name'] || $service->category !== $data['category'])) {
            throw ValidationException::withMessages(['name' => 'A service used in visits cannot be renamed or recategorized. Make it inactive and add a new service to preserve history.']);
        }
        $service->update($data); return back()->with('status', 'Service updated; existing visits remain linked.');
    }
    private function validateData(Request $request, ?TherapyService $service = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('therapy_services')->where('category', $request->input('category'))->ignore($service?->id)],
            'category' => ['required', Rule::in(['therapy', 'activity'])], 'is_active' => ['required', 'boolean']]);
    }
}
