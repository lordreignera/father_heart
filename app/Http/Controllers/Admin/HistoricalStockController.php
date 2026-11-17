<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HistoricalStockReferences;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoricalStockController extends Controller
{
    public function index(Request $request, HistoricalStockReferences $stock): View
    {
        $filtered = $stock->filtered($request);
        $columns = HistoricalStockReferences::columns($filtered);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $records = new LengthAwarePaginator($filtered->forPage($page, 25)->values(), $filtered->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('admin.inventory.historical', ['records' => $records, 'columns' => $columns, 'sources' => HistoricalStockReferences::TABLES]);
    }

    public function export(Request $request, HistoricalStockReferences $stock): StreamedResponse
    {
        $records = $stock->filtered($request);
        $columns = HistoricalStockReferences::columns($records);
        return response()->streamDownload(function () use ($records, $columns) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_map([HistoricalStockReferences::class, 'csvValue'], ['Source table', 'Source record ID', ...$columns->all()]), ',', '"', '');
            foreach ($records as $record) {
                $values = [$record['source'], $record['source_id']];
                foreach ($columns as $column) {
                    $value = $record['fields'][$column] ?? '';
                    // Excel otherwise guesses a locale for ambiguous historical dates.
                    if (strtolower($column) === 'date' && $value !== '' && ! str_starts_with($value, "'")) $value = "'".$value;
                    $values[] = $value;
                }
                fputcsv($output, array_map([HistoricalStockReferences::class, 'csvValue'], $values), ',', '"', '');
            }
            fclose($output);
        }, 'historical-stock-references.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
