<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntakeCodeSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.intake-code', ['settings' => DB::table('intake_code_settings')->where('id', 1)->first()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'date_format' => ['required', Rule::in(['mY', 'Ym'])],
            'sequence_reset' => ['required', Rule::in(['monthly', 'continuous'])],
            'starting_number' => ['required', 'integer', 'between:1,999999999'],
        ]);
        $data['prefix'] = strtoupper($data['prefix'] ?? '');
        DB::transaction(function () use ($data): void {
            DB::table('intake_code_settings')->where('id', 1)->lockForUpdate()->first();
            DB::table('intake_code_settings')->where('id', 1)->update($data + ['updated_at' => now()]);
        });
        return back()->with('status', 'Intake code settings saved. Existing codes are unchanged.');
    }
}
