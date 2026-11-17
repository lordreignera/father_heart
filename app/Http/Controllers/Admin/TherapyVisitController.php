<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyChild;
use App\Models\TherapyService;
use App\Models\TherapyVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TherapyVisitController extends Controller
{
    public function store(Request $request, TherapyChild $child)
    {
        $data = $this->validated($request);
        $visit = DB::transaction(function () use ($data, $child, $request) {
            $visit = $child->visits()->create(['visited_on' => $data['visited_on'], 'status' => $data['status'], 'notes' => $data['notes'] ?? null, 'recorded_by' => $request->user()->id]);
            $visit->services()->sync($data['services']); return $visit;
        });
        return redirect()->route('admin.therapy.visits.show', $visit)->with('status', 'Visit recorded.');
    }
    public function show(TherapyVisit $visit)
    {
        $visit->load(['child.person', 'services', 'recorder']);
        return view('admin.therapy.visit-show', compact('visit'));
    }
    public function edit(TherapyVisit $visit)
    {
        $visit->load(['child.person', 'services']);
        $services = TherapyService::where('is_active', true)->orWhereIn('id', $visit->services->modelKeys())->orderBy('category')->orderBy('name')->get();
        return view('admin.therapy.visit-form', compact('visit', 'services'));
    }
    public function update(Request $request, TherapyVisit $visit)
    {
        $data = $this->validated($request, $visit);
        DB::transaction(function () use ($visit, $data) {
            $visit->update(['visited_on' => $data['visited_on'], 'status' => $data['status'], 'notes' => $data['notes'] ?? null]);
            $visit->services()->sync($data['services']);
        });
        return redirect()->route('admin.therapy.visits.show', $visit)->with('status', 'Visit updated.');
    }
    private function validated(Request $request, ?TherapyVisit $visit = null): array
    {
        $available = TherapyService::where('is_active', true)->pluck('id');
        if ($visit) $available = $available->merge($visit->services()->pluck('therapy_services.id'))->unique();
        return $request->validate(['visited_on' => ['required', 'date'], 'status' => ['required', Rule::in(['completed', 'cancelled', 'missed'])],
            'notes' => ['nullable', 'string', 'max:10000'], 'services' => ['required', 'array', 'min:1'], 'services.*' => ['required', 'integer', 'distinct', Rule::in($available->all())]]);
    }
}
