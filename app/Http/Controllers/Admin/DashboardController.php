<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\District;
use App\Models\DistributionEvent;
use App\Models\Person;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\LegacySourceRecord;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->filled('month') ? Carbon::createFromFormat('!Y-m', $request->input('month'), 'Africa/Kampala') : now('Africa/Kampala')->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();
        $user = $request->user();

        return view('admin.overview', [
            'month' => $month,
            'personCount' => $user->can('route-admin-people-index') ? Person::whereDoesntHave('assessments.legacySource')->whereBetween('created_at', [$startUtc, $endUtc])->count() : null,
            'ongoingCount' => $user->can('route-admin-assessments-index') ? Assessment::whereIn('status', ['draft', 'assessed', 'waitlisted'])
                ->where(function ($query) use ($start, $end, $startUtc, $endUtc) {
                    $query->whereBetween('assessed_on', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(fn ($query) => $query->whereNull('assessed_on')->whereDoesntHave('legacySource')->whereBetween('created_at', [$startUtc, $endUtc]));
                })->count() : null,
            'events' => $user->can('route-admin-distributions-index') ? DistributionEvent::with('district')
                ->whereIn('status', ['planned', 'active'])->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('event_date')->get() : collect(),
            'stockIn' => $user->can('route-admin-inventory-index') ? InventoryMovement::where('type', 'in')->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])->count() : null,
            'stockOut' => $user->can('route-admin-inventory-index') ? InventoryMovement::where('type', 'out')->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])->count() : null,
            'stockOnHand' => $user->can('route-admin-inventory-index') ? InventoryItem::whereNotNull('opening_date')->count() : null,
            'stockUncounted' => $user->can('route-admin-inventory-index') ? InventoryItem::whereNull('opening_date')->count() : null,
            'stockByItem' => $user->can('route-admin-inventory-index') ? InventoryMovement::with('item')->selectRaw("inventory_item_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) AS received, SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) AS issued")
                ->whereIn('type', ['in', 'out'])->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])->groupBy('inventory_item_id')->get() : collect(),
            'importedCount' => $user->can('route-admin-people-index') ? LegacySourceRecord::where('status', 'imported')->count() : null,
        ]);
    }
}
