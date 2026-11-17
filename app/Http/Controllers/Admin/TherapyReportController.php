<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapyService;
use App\Models\TherapyVisit;
use App\Support\HistoricalStockReferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TherapyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request);
        $completed = (clone $query)->where('status', 'completed');
        $visitsCount = (clone $completed)->count();
        $childrenCount = (clone $completed)->distinct()->count('therapy_child_id');
        $services = TherapyService::orderBy('category')->orderBy('name')->get();
        $serviceCounts = DB::table('therapy_visit_service')->join('therapy_services', 'therapy_services.id', '=', 'therapy_visit_service.therapy_service_id')
            ->whereIn('therapy_visit_service.therapy_visit_id', (clone $completed)->select('therapy_visits.id'))
            ->when($request->filled('service_id'), fn ($q) => $q->where('therapy_services.id', $request->integer('service_id')))
            ->select('therapy_services.name', 'therapy_services.category')->selectRaw('count(*) as visit_count')
            ->groupBy('therapy_services.id', 'therapy_services.name', 'therapy_services.category')->orderBy('therapy_services.name')->get();
        $visits = $query->with(['child.person', 'services'])->orderByDesc('visited_on')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('admin.therapy.reports', compact('visits', 'visitsCount', 'childrenCount', 'services', 'serviceCounts'));
    }
    public function export(Request $request)
    {
        $query = $this->filtered($request)->with(['child.person', 'services'])->orderByDesc('visited_on')->orderByDesc('id');
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Child number', 'Child name', 'Visit status', 'Services'], ',', '"', '');
            foreach ($query->lazy(200) as $visit) fputcsv($out, array_map([HistoricalStockReferences::class, 'csvValue'], ["'".$visit->visited_on->format('Y-m-d'), $visit->therapy_child_id, $visit->child?->person?->full_name, $visit->status, $visit->services->pluck('name')->join(', ')]), ',', '"', '');
            fclose($out);
        }, 'therapy-visits.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
    private function filtered(Request $request)
    {
        $data = $request->validate(['date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'service_id' => ['nullable', 'integer', 'exists:therapy_services,id'], 'status' => ['nullable', Rule::in(['completed', 'cancelled', 'missed'])], 'q' => ['nullable', 'string', 'max:120']]);
        return TherapyVisit::query()->when($data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('visited_on', '>=', $v))
            ->when($data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('visited_on', '<=', $v))
            ->when($data['service_id'] ?? null, fn ($q, $v) => $q->whereHas('services', fn ($s) => $s->where('therapy_services.id', $v)))
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['q'] ?? null, fn ($q, $v) => $q->whereHas('child.person', fn ($p) => $p->where('full_name', 'like', '%'.$v.'%')));
    }
}
