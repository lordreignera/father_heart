<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Support\AdminRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(Request $request): View
    {
        $districts = District::query()->withCount('subcounties')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.locations.districts.index', ['districts' => $districts, 'newDistrict' => new District]);
    }

    public function create(): View
    {
        return view('admin.locations.districts.form', ['district' => new District]);
    }

    public function store(Request $request): RedirectResponse
    {
        $district = District::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:district,city'],
            'ubos_code' => ['nullable', 'string', 'max:16'],
            'is_active' => ['required', 'boolean'],
        ]));

        return AdminRedirect::firstAllowed(['admin.districts.index' => [], 'admin.districts.edit' => [$district]])->with('status', 'District saved.');
    }

    public function edit(District $district): View
    {
        return view('admin.locations.districts.form', compact('district'));
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $district->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:district,city'],
            'ubos_code' => ['nullable', 'string', 'max:16'],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'District updated.');
    }

    public function destroy(District $district): RedirectResponse
    {
        if ($district->subcounties()->exists()) {
            return back()->withErrors(['district' => 'Archive its subcounties first, or mark this district inactive.']);
        }
        $district->delete();

        return AdminRedirect::firstAllowed(['admin.districts.index' => []])->with('status', 'District archived.');
    }
}
