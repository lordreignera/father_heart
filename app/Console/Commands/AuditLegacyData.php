<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AuditLegacyData extends Command
{
    protected $signature = 'legacy:audit';

    protected $description = 'Read and count the historical fathersheart database without importing or changing it';

    public function handle(): int
    {
        $target = DB::connection();
        if ($target->getDriverName() !== 'mysql' || $target->getDatabaseName() !== 'fathersheart2') {
            throw new RuntimeException('The target connection must be the new fathersheart2 MySQL database.');
        }

        $config = config('database.connections.mysql');
        $config['url'] = null;
        $config['database'] = 'fathersheart';
        config(['database.connections.legacy_audit' => $config]);
        DB::purge('legacy_audit');
        $source = DB::connection('legacy_audit');
        if ($source->getDatabaseName() !== 'fathersheart') {
            throw new RuntimeException('The source connection is not the historical fathersheart database.');
        }

        // Applies to every transaction in this session, including autocommit statements.
        $source->statement('SET SESSION TRANSACTION READ ONLY');
        $tables = $source->select("SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", ['fathersheart']);
        $columns = $source->select('SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name FROM information_schema.columns WHERE TABLE_SCHEMA = ?', ['fathersheart']);
        $byTable = collect($columns)->groupBy('table_name');
        $report = ['generated_at' => now()->toIso8601String(), 'source' => 'fathersheart', 'target' => 'fathersheart2', 'tables' => [], 'recipient_tables' => 0, 'recipient_rows' => 0, 'recipient_diagnostics' => ['blank_names' => 0, 'blank_phones' => 0, 'duplicate_ids' => 0, 'blank_dates' => 0, 'iso_date_shape' => 0, 'slash_date_shape' => 0, 'other_date_shape' => 0, 'tables_without_date' => 0], 'errors' => []];

        foreach ($tables as $table) {
            $name = (string) $table->name;
            $quoted = '`'.str_replace('`', '``', $name).'`';
            try {
                $count = (int) $source->selectOne('SELECT COUNT(*) AS total FROM '.$quoted)->total;
            } catch (\Throwable $exception) {
                $report['errors'][] = ['table' => $name, 'error' => 'Could not count table.'];
                continue;
            }
            $names = ($byTable->get($name) ?? collect())->pluck('column_name')->map(fn ($name) => strtolower($name));
            $recipientTable = $names->contains('id') && $names->contains('fullname') && $names->contains('phone') && $names->contains('givemobility');
            if ($recipientTable) {
                $report['recipient_tables']++;
                $report['recipient_rows'] += $count;
                try {
                    $dateExpression = $names->contains('date') ? "SUM(CASE WHEN `date` IS NULL OR TRIM(CAST(`date` AS CHAR)) = '' THEN 1 ELSE 0 END)" : '0';
                    $isoExpression = $names->contains('date') ? "SUM(CASE WHEN TRIM(CAST(`date` AS CHAR)) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN 1 ELSE 0 END)" : '0';
                    $slashExpression = $names->contains('date') ? "SUM(CASE WHEN TRIM(CAST(`date` AS CHAR)) REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN 1 ELSE 0 END)" : '0';
                    $otherExpression = $names->contains('date') ? "SUM(CASE WHEN `date` IS NOT NULL AND TRIM(CAST(`date` AS CHAR)) <> '' AND TRIM(CAST(`date` AS CHAR)) NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND TRIM(CAST(`date` AS CHAR)) NOT REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN 1 ELSE 0 END)" : '0';
                    $diagnostics = $source->selectOne("SELECT SUM(CASE WHEN fullname IS NULL OR TRIM(fullname) = '' THEN 1 ELSE 0 END) AS blank_names, SUM(CASE WHEN phone IS NULL OR TRIM(phone) = '' THEN 1 ELSE 0 END) AS blank_phones, COUNT(*) - COUNT(DISTINCT id) AS duplicate_ids, {$dateExpression} AS blank_dates, {$isoExpression} AS iso_date_shape, {$slashExpression} AS slash_date_shape, {$otherExpression} AS other_date_shape FROM {$quoted}");
                    foreach (['blank_names', 'blank_phones', 'duplicate_ids', 'blank_dates', 'iso_date_shape', 'slash_date_shape', 'other_date_shape'] as $key) {
                        $report['recipient_diagnostics'][$key] += (int) $diagnostics->{$key};
                    }
                    if (! $names->contains('date')) {
                        $report['recipient_diagnostics']['tables_without_date']++;
                    }
                } catch (\Throwable $exception) {
                    $report['errors'][] = ['table' => $name, 'error' => 'Could not assess recipient fields.'];
                }
            }
            $report['tables'][] = ['name' => $name, 'engine' => $table->engine, 'rows' => $count, 'recipient_table' => $recipientTable, 'columns' => $names->values()->all()];
        }

        $report['target_engines'] = $target->select("SELECT ENGINE AS engine, COUNT(*) AS table_count FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' GROUP BY ENGINE", ['fathersheart2']);
        $report['complete'] = count($report['errors']) === 0 && count($tables) > 0;
        $path = 'legacy-audits/audit-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
        if (! Storage::disk('local')->put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Could not save the audit report.');
        }

        $this->info('Source: fathersheart; target: fathersheart2. No source records were changed.');
        $this->line('Tables counted: '.count($report['tables']).'; recipient-shaped tables: '.$report['recipient_tables'].'; recipient-shaped rows: '.$report['recipient_rows'].'.');
        $this->line('Recipient diagnostics: '.json_encode($report['recipient_diagnostics'], JSON_THROW_ON_ERROR));
        $this->line('Audit report: storage/app/private/'.$path);
        if (! $report['complete']) {
            $this->error('The audit is incomplete; review the report before proceeding.');
            return self::FAILURE;
        }
        $this->warn('This audit does not import records, verify backups, or establish inventory opening balances.');
        return self::SUCCESS;
    }
}
