<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Support\HistoricalStockReferences;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $movements = $this->filtered($request)->paginate(25)->withQueryString();
        return view('admin.inventory.movements', compact('movements'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request);
        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Date', 'Item', 'Unit', 'Movement', 'Quantity', 'Reference', 'Notes', 'Recorded by'], ',', '"', '');
            foreach ($query->lazy(200) as $movement) {
                $values = ["'".$movement->occurred_on->format('Y-m-d'), $movement->item?->name, $movement->item?->unit,
                    $movement->type, $movement->quantity, $movement->reference, $movement->notes, $movement->recorder?->name];
                fputcsv($output, array_map([HistoricalStockReferences::class, 'csvValue'], $values), ',', '"', '');
            }
            fclose($output);
        }, 'inventory-movements.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function filtered(Request $request): Builder
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'type' => ['nullable', Rule::in(['opening', 'in', 'out'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('date_from') ? 'after_or_equal:date_from' : null]),
        ]);
        return InventoryMovement::query()->with(['item', 'recorder'])
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('reference', 'like', '%'.$filters['q'].'%')->orWhereHas('item', fn (Builder $item) => $item->where('name', 'like', '%'.$filters['q'].'%'))))
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('occurred_on', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('occurred_on', '<=', $filters['date_to']))
            ->orderByDesc('occurred_on')->orderByDesc('id');
    }
}
