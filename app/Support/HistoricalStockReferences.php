<?php

namespace App\Support;

use App\Models\LegacySourceRecord;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class HistoricalStockReferences
{
    public const TABLES = [
        'inventory', 'cane', 'white_cane', 'walkers', 'crutches', 'gen1', 'gen2_s_13', 'gen2_m_17', 'gen2_l_18_5', 'gen2_xl_20',
        'gen3_s_13', 'gen3_m_17', 'gen3_l_18_5', 'gen3_xl_20', 'jaf', 'hb_11', 'hb_13',
    ];

    public function filtered(Request $request): Collection
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:160'], 'source' => ['nullable', Rule::in(self::TABLES)]]);
        $term = mb_strtolower(trim($filters['q'] ?? ''));
        return LegacySourceRecord::where('source_database', 'fathersheart')->whereIn('source_table', self::TABLES)
            ->when(filled($filters['source'] ?? null), fn ($query) => $query->where('source_table', $filters['source']))
            ->orderBy('source_table')->orderBy('id')->get()->map(fn ($record) => [
                'source' => $record->source_table,
                'source_id' => $record->source_row_id,
                'fields' => collect($record->raw_payload)->map(fn ($value) => self::text($value))->all(),
            ])->filter(function ($record) use ($term) {
                if ($term === '') return true;
                return str_contains(mb_strtolower(implode(' ', [$record['source'], $record['source_id'], ...array_keys($record['fields']), ...array_values($record['fields'])])), $term);
            })->values();
    }

    public static function columns(Collection $records): Collection
    {
        return $records->flatMap(fn ($record) => array_keys($record['fields']))->unique()->values();
    }

    public static function text(mixed $value): string
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_scalar($value)) return (string) $value;
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function csvValue(mixed $value): string
    {
        $text = self::text($value);
        return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $text) || preg_match('/^0\d+$/', $text) ? "'".$text : $text;
    }
}
