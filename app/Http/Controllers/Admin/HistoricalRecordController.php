<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegacySourceRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoricalRecordController extends Controller
{
    public function index(Request $request, string $category): View
    {
        $categories = [
            'locations' => ['permission' => 'manage-people', 'tables' => ['all_districts' => ['id', 'district', 'distribution'], 'district' => ['id', 'district', 'subcounty']]],
            'events' => ['permission' => 'manage-distributions', 'tables' => ['distribution' => ['id', 'distribution', 'district']]],
            'team' => ['permission' => 'manage-staff', 'tables' => ['staff' => ['id', 'firstname', 'lastname', 'username', 'phone'], 'volunteer' => ['id', 'firstname', 'lastname', 'username', 'phone'], 'employee' => ['id', 'username']]],
            'feedback' => ['permission' => 'manage-staff', 'also' => 'manage-people', 'tables' => ['ratings' => ['id', 'staff', 'recipient', 'rating']]],
            'media' => ['permission' => 'manage-people', 'tables' => ['images' => ['id', 'before', 'after']]],
        ];
        abort_unless(isset($categories[$category]), 404);
        $config = $categories[$category];
        // Category-specific access is enforced by AuthorizeAdminRoute before this action.
        $records = LegacySourceRecord::where('source_database', 'fathersheart')->whereIn('source_table', array_keys($config['tables']))
            ->orderBy('source_table')->orderBy('id')->paginate(25)->through(function ($record) use ($config) {
                $allowed = $config['tables'][$record->source_table];
                return ['table' => $record->source_table, 'id' => $record->source_row_id, 'fields' => array_intersect_key($record->raw_payload, array_flip($allowed))];
            });
        return view('admin.historical.index', compact('category', 'records'));
    }
}
