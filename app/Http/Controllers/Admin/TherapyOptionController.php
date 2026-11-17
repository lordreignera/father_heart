<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TherapyOptionController extends Controller
{
    public const CATEGORIES = ['disabilities' => 'Disability / diagnosis', 'mobility' => 'Mobility', 'communication' => 'Communication',
        'previous_therapy' => 'Previous therapy', 'musical_interests' => 'Musical interests', 'session_type' => 'Session type', 'frequency' => 'Frequency'];
    public function index(Request $request) {
        $filters = $request->validate(['category' => ['nullable', Rule::in(array_keys(self::CATEGORIES))], 'q' => ['nullable', 'string', 'max:120']]);
        if (!empty($filters['category'])) return redirect()->route('admin.therapy.catalogues.index', $filters['category']);
        return view('admin.therapy.catalogue-links');
    }
    public function store(Request $request) { abort(410, 'This form has moved. Please reload the settings page.'); }
    public function update(Request $request, TherapyOption $option) { abort(410, 'This form has moved. Please reload the settings page.'); }
}
