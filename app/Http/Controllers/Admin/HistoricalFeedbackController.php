<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoricalFeedbackProfile;
use App\Models\LegacySourceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoricalFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->boolean('archived');
        $filtered = $this->filteredRecords($request);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $records = new LengthAwarePaginator($filtered->forPage($page, 25)->values(), $filtered->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.historical.feedback', compact('records', 'archived'));
    }

    public function export(Request $request): StreamedResponse
    {
        $records = $this->filteredRecords($request);
        return response()->streamDownload(function () use ($records) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Source number', 'Staff reference', 'Recipient reference', 'Rating / feedback'], ',', '"', '');
            foreach ($records as $record) {
                $values = [$record['source_id'], $record['staff'], $record['recipient'], $record['rating']];
                fputcsv($output, array_map(fn ($value) => $this->safeCsvValue($value), $values), ',', '"', '');
            }
            fclose($output);
        }, 'historical-feedback.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function filteredRecords(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = LegacySourceRecord::with('feedbackProfile')->where('source_database', 'fathersheart')->where('source_table', 'ratings');
        if ($request->boolean('archived')) $query->whereHas('feedbackProfile', fn ($query) => $query->whereNotNull('archived_at'));
        else $query->whereDoesntHave('feedbackProfile', fn ($query) => $query->whereNotNull('archived_at'));
        $term = mb_strtolower(trim($filters['q'] ?? ''));

        // Historical source and correction values are encrypted, so matching must happen after decryption.
        return $query->orderBy('id')->get()->map(function ($record) {
            $values = $record->feedbackProfile ? $record->feedbackProfile->only(['staff', 'recipient', 'rating']) : $this->defaults($record);
            return ['id' => $record->id, 'source_id' => $record->source_row_id] + $values;
        })->filter(fn ($record) => $term === '' || str_contains(mb_strtolower(implode(' ', [$record['source_id'], $record['staff'], $record['recipient'], $record['rating']])), $term))->values();
    }

    private function safeCsvValue($value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $value) || preg_match('/^0\d+$/', $value) ? "'".$value : $value;
    }

    public function update(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->authorizeRecord($request, $record);
        $data = $request->validate(['staff' => ['nullable', 'string', 'max:255'], 'recipient' => ['nullable', 'string', 'max:255'], 'rating' => ['nullable', 'string', 'max:10000']]);
        DB::transaction(function () use ($record, $data) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $profile = $this->profile($original);
            if ($profile->archived_at) throw ValidationException::withMessages(['record' => 'Restore this entry before editing it.']);
            $profile->update($data);
        });
        return redirect()->route('admin.historical.feedback.index')->with('status', 'Feedback updated. The encrypted historical original remains unchanged.');
    }

    public function archive(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->authorizeRecord($request, $record);
        DB::transaction(function () use ($record) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->profile($original)->update(['archived_at' => now()]);
        });
        return redirect()->route('admin.historical.feedback.index')->with('status', 'Feedback archived. The original is preserved and can be restored.');
    }

    public function restore(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->authorizeRecord($request, $record);
        HistoricalFeedbackProfile::where('legacy_source_record_id', $record->id)->whereNotNull('archived_at')->update(['archived_at' => null]);
        return redirect()->route('admin.historical.feedback.index', ['archived' => 1])->with('status', 'Feedback restored.');
    }

    private function authorizeRecord(Request $request, LegacySourceRecord $record): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($record->source_database === 'fathersheart' && $record->source_table === 'ratings', 404);
    }

    private function profile(LegacySourceRecord $record): HistoricalFeedbackProfile
    {
        return HistoricalFeedbackProfile::firstOrCreate(['legacy_source_record_id' => $record->id], $this->defaults($record));
    }

    private function defaults(LegacySourceRecord $record): array
    {
        $payload = $record->raw_payload;
        $values = [];
        foreach (['staff', 'recipient', 'rating'] as $key) {
            $value = $payload[$key] ?? null;
            $values[$key] = is_scalar($value) ? (string) $value : null;
        }
        return $values;
    }
}
