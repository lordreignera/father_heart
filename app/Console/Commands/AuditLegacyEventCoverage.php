<?php

namespace App\Console\Commands;

use App\Models\LegacySourceRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuditLegacyEventCoverage extends Command
{
    protected $signature = 'legacy:audit-event-coverage';
    protected $description = 'Read-only comparison of old event rows, preserved source fields and linked assessments';

    public function handle(): int
    {
        $db = DB::connection();
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== 'fathersheart2') throw new RuntimeException('Unexpected target database.');
        $columns = $db->table('information_schema.columns')->where('table_schema', 'fathersheart')
            ->whereIn('column_name', ['id', 'fullname', 'phone', 'givemobility', 'date'])->selectRaw('TABLE_NAME as legacy_name, COLUMN_NAME as legacy_column')->get();
        $tables = $columns->groupBy('legacy_name')->filter(fn ($fields) => $fields->pluck('legacy_column')->unique()->count() === 5)->keys()->sort()->values();
        $events = $db->table('distribution_events')->whereNotNull('legacy_table')->get(['id', 'legacy_table'])->groupBy('legacy_table');
        $totals = ['source_tables' => 0, 'source_rows' => 0, 'preserved_rows' => 0, 'standalone_rows' => 0, 'standalone_preserved' => 0, 'missing_events' => 0, 'duplicate_events' => 0, 'missing_source_tables' => 0,
            'unsafe_table_names' => 0, 'duplicate_old_ids' => 0, 'duplicate_preserved_ids' => 0, 'missing_originals' => 0, 'extra_originals' => 0,
            'field_mismatches' => 0, 'missing_assessments' => 0, 'missing_people' => 0, 'duplicate_assessment_mappings' => 0, 'wrong_event_links' => 0];
        $totals['duplicate_assessment_mappings'] = (int) $db->table('legacy_source_records')->where('source_database', 'fathersheart')
            ->where('status', 'imported')->where('mapped_type', 'assessment')->whereNotNull('mapped_id')->groupBy('mapped_id')
            ->havingRaw('count(*) > 1')->selectRaw('count(*) - 1 as excess')->get()->sum('excess');
        $missingSourceTables = $events->keys()->diff($tables);
        $totals['missing_source_tables'] = $missingSourceTables->count();

        foreach ($tables as $table) {
            $standalone = $table === 'recipients'; // This intake table intentionally has no event.
            if (! $standalone) $totals['source_tables']++;
            if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) { $totals['unsafe_table_names']++; continue; }
            $matchingEvents = $events->get($table, collect());
            if (! $standalone && $matchingEvents->isEmpty()) $totals['missing_events']++;
            if ($matchingEvents->count() > 1) $totals['duplicate_events']++;
            $expectedEventId = $matchingEvents->count() === 1 ? $matchingEvents->first()->id : null;
            $old = $db->table('fathersheart.'.$table)->get(['id', 'date', 'givemobility']);
            $originals = LegacySourceRecord::where('source_database', 'fathersheart')->where('source_table', $table)->get();
            $totals[$standalone ? 'standalone_rows' : 'source_rows'] += $old->count();
            $totals[$standalone ? 'standalone_preserved' : 'preserved_rows'] += $originals->count();
            $oldById = $old->groupBy(fn ($row) => (string) $row->id);
            $originalsById = $originals->groupBy('source_row_id');
            $totals['duplicate_old_ids'] += $oldById->sum(fn ($rows) => max(0, $rows->count() - 1));
            $totals['duplicate_preserved_ids'] += $originalsById->sum(fn ($rows) => max(0, $rows->count() - 1));
            $totals['missing_originals'] += $oldById->keys()->diff($originalsById->keys())->count();
            $totals['extra_originals'] += $originalsById->keys()->diff($oldById->keys())->count();
            $mappedIds = $originals->where('status', 'imported')->where('mapped_type', 'assessment')->pluck('mapped_id')->filter()->unique();
            $assessments = $db->table('assessments as a')->leftJoin('people as p', 'p.id', '=', 'a.person_id')->whereIn('a.id', $mappedIds)
                ->get(['a.id', 'a.distribution_event_id', 'p.id as existing_person_id'])->keyBy('id');
            foreach ($originals as $original) {
                $source = $oldById->get($original->source_row_id);
                if ($source && $source->count() === 1) {
                    $oldRow = $source->first();
                    $payload = $original->raw_payload;
                    foreach (['date', 'givemobility'] as $field) {
                        if (! array_key_exists($field, $payload) || ! $this->sameValue($payload[$field], $oldRow->{$field})) { $totals['field_mismatches']++; break; }
                    }
                }
                if ($original->status !== 'imported' || $original->mapped_type !== 'assessment' || ! $assessments->has($original->mapped_id)) $totals['missing_assessments']++;
                else {
                    $assessment = $assessments->get($original->mapped_id);
                    if ($assessment->existing_person_id === null) $totals['missing_people']++;
                    if (($standalone && $assessment->distribution_event_id !== null) || ($expectedEventId !== null && (string) $assessment->distribution_event_id !== (string) $expectedEventId)) $totals['wrong_event_links']++;
                }
            }
        }
        $this->line(json_encode($totals, JSON_THROW_ON_ERROR));
        $issues = array_diff_key($totals, array_flip(['source_tables', 'source_rows', 'preserved_rows', 'standalone_rows', 'standalone_preserved']));
        if (array_sum($issues) > 0) { $this->error('Differences need review; no records were changed.'); return self::FAILURE; }
        return self::SUCCESS;
    }

    private function sameValue(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) return $left === null && $right === null;
        return is_scalar($left) && is_scalar($right) && (string) $left === (string) $right;
    }
}
