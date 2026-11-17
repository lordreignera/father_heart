<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Subcounty;
use App\Support\AdminRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubcountyController extends Controller
{
    public function index(Request $request): View
    {
        $subcounties = Subcounty::query()->with('district')
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->integer('district_id')))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.locations.subcounties.index', [
            'subcounties' => $subcounties,
            'districts' => District::orderBy('name')->get(['id', 'name', 'kind']),
            'activeDistricts' => District::where('is_active', true)->orderBy('name')->get(),
            'newSubcounty' => new Subcounty,
        ]);
    }

    public function create(): View
    {
        return view('admin.locations.subcounties.form', [
            'subcounty' => new Subcounty,
            'districts' => District::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $subcounty = Subcounty::create($this->validated($request));

        return AdminRedirect::firstAllowed(['admin.subcounties.index' => [], 'admin.subcounties.edit' => [$subcounty]])->with('status', 'Subcounty-tier unit saved.');
    }

    public function edit(Subcounty $subcounty): View
    {
        return view('admin.locations.subcounties.form', [
            'subcounty' => $subcounty,
            'districts' => District::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subcounty $subcounty): RedirectResponse
    {
        $subcounty->update($this->validated($request));

        return back()->with('status', 'Subcounty-tier unit updated.');
    }

    public function destroy(Subcounty $subcounty): RedirectResponse
    {
        $subcounty->delete();

        return AdminRedirect::firstAllowed(['admin.subcounties.index' => []])->with('status', 'Subcounty-tier unit archived.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'district_id' => ['required', Rule::exists('districts', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:subcounty,town_council,division'],
            'ubos_code' => ['nullable', 'string', 'max:16'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
