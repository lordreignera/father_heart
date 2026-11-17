<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TherapyCatalogues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TherapyCatalogueController extends Controller
{
    public function index(Request $request, string $catalogue)
    {
        $table = TherapyCatalogues::table($catalogue);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $options = DB::table($table)->when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))->orderBy('name')->get();
        return view('admin.therapy.catalogue', ['catalogue' => $catalogue, 'definition' => TherapyCatalogues::ITEMS[$catalogue], 'options' => $options]);
    }

    public function store(Request $request, string $catalogue)
    {
        $table = TherapyCatalogues::table($catalogue);
        $data = $request->validate(['name' => ['required', 'string', 'max:180', Rule::unique($table, 'name')], 'is_active' => ['required', 'boolean']]);
        DB::table($table)->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        return back()->with('status', 'Option added.');
    }

    public function update(Request $request, string $catalogue, string $entry)
    {
        $table = TherapyCatalogues::table($catalogue);
        abort_unless(ctype_digit($entry) && DB::table($table)->where('id', $entry)->exists(), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:180', Rule::unique($table, 'name')->ignore($entry)], 'is_active' => ['required', 'boolean']]);
        DB::table($table)->where('id', $entry)->update($data + ['updated_at' => now()]);
        return back()->with('status', 'Option updated. Existing registrations keep their recorded information.');
    }
}
