<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\DistributionEvent;
use App\Support\LegacyEventLocations;
use App\Support\AdminRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DistributionEventController extends Controller
{
    public function index(): View
    {
        return view('admin.distributions.index', [
            'events' => DistributionEvent::with(['district', 'subcounty'])->withCount('assessments')
                ->orderByDesc('event_date')->orderByDesc('id')->paginate(25),
            'districts' => District::orderBy('name')->get(),
            'newEvent' => new DistributionEvent,
            'historicalDistricts' => app(LegacyEventLocations::class)->registryDistricts(DB::connection()),
        ]);
    }

    public function create(): View
    {
        return $this->form(new DistributionEvent);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = DistributionEvent::create($this->validated($request));

        return AdminRedirect::firstAllowed(['admin.distributions.show' => [$event], 'admin.distributions.index' => []])->with('status', 'Distribution event created.');
    }

    public function show(Request $request, DistributionEvent $distribution): View
    {
        $distribution->load(['district', 'subcounty']);

        return view('admin.distributions.show', [
            'event' => $distribution,
            'assessments' => $request->user()->can('route-admin-assessments-show') ? $distribution->assessments()->with(['person', 'legacySource'])->latest()->paginate(25) : null,
            'historicalDistricts' => app(LegacyEventLocations::class)->registryDistricts(DB::connection()),
        ]);
    }

    public function edit(DistributionEvent $distribution): View
    {
        return $this->form($distribution);
    }

    public function update(Request $request, DistributionEvent $distribution): RedirectResponse
    {
        $distribution->update($this->validated($request));

        return AdminRedirect::firstAllowed(['admin.distributions.show' => [$distribution], 'admin.distributions.index' => []])->with('status', 'Distribution event updated.');
    }

    public function destroy(DistributionEvent $distribution): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($distribution): bool {
            $event = DistributionEvent::whereKey($distribution->id)->lockForUpdate()->firstOrFail();
            if ($event->assessments()->exists() || $event->legacy_table) return false;
            $event->delete();
            return true;
        });

        if (! $deleted) return redirect()->route('admin.distributions.index')->withErrors(['event' => 'Events with assessments or historical source records cannot be deleted.']);
        return redirect()->route('admin.distributions.index')->with('status', 'Distribution event deleted.');
    }

    private function form(DistributionEvent $event): View
    {
        return view('admin.distributions.form', [
            'event' => $event,
            'districts' => District::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'event_date' => ['nullable', 'date'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'subcounty_id' => ['nullable', 'exists:subcounties,id'],
            'status' => ['required', 'in:planned,active,completed,historical'],
            'notes' => ['nullable', 'string'],
        ]);
        if (filled($data['subcounty_id'] ?? null) && ! DB::table('subcounties')
            ->where('id', $data['subcounty_id'])->where('district_id', $data['district_id'] ?? 0)->exists()) {
            throw ValidationException::withMessages(['subcounty_id' => 'Choose a subcounty in the selected district.']);
        }

        return $data;
    }
}
