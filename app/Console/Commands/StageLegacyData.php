<?php

namespace App\Console\Commands;

use App\Support\LegacyArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StageLegacyData extends Command
{
    protected $signature = 'legacy:stage {--write : Store encrypted originals after all checks} {--rehearsal : Use only the isolated restored copies}';

    protected $description = 'Validate or stage historical originals, without creating people, assessments, stock or media';

    public function handle(LegacyArchive $archive): int
    {
        $rehearsal = (bool) $this->option('rehearsal');
        $sourceName = $rehearsal ? 'fathersheart_restoretest_fd1ca66b' : 'fathersheart';
        $targetName = $rehearsal ? 'fathersheart2_restoretest_fd1ca66b' : 'fathersheart2';
        $config = config('database.connections.mysql');
        $config['url'] = null;
        foreach (['legacy_stage_source' => $sourceName, 'legacy_stage_target' => $targetName] as $name => $database) {
            config(['database.connections.'.$name => array_merge($config, ['database' => $database])]);
            DB::purge($name);
        }
        $source = DB::connection('legacy_stage_source');
        $target = DB::connection('legacy_stage_target');
        if ($source->getDriverName() !== 'mysql' || $target->getDriverName() !== 'mysql'
            || $source->selectOne('SELECT DATABASE() AS db')->db !== $sourceName
            || $target->selectOne('SELECT DATABASE() AS db')->db !== $targetName || $sourceName === $targetName) {
            throw new RuntimeException('Unexpected source or target connection; staging stopped.');
        }
        $source->statement('SET SESSION TRANSACTION READ ONLY');
        $required = $target->select("SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('legacy_source_records', 'legacy_import_batches')", [$targetName]);
        if (count($required) !== 2 || collect($required)->contains(fn ($table) => $table->engine !== 'InnoDB')) {
            throw new RuntimeException('Archive tables must both use InnoDB.');
        }
        $tables = $source->select("SELECT TABLE_NAME AS name FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", [$sourceName]);
        $keys = $source->select("SELECT TABLE_NAME AS name, COUNT(*) AS columns_count, MIN(COLUMN_NAME) AS column_name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND CONSTRAINT_NAME = 'PRIMARY' GROUP BY TABLE_NAME", [$sourceName]);
        $byTable = collect($keys)->keyBy('name');
        foreach ($tables as $table) {
            $key = $byTable->get($table->name);
            if (! $key || (int) $key->columns_count !== 1 || strtolower($key->column_name) !== 'id') {
                throw new RuntimeException('Unsupported source primary key in table '.$table->name);
            }
        }
        if (count($tables) !== 215) throw new RuntimeException('Unexpected source table count; review before proceeding.');
        if (! $target->selectOne("SELECT GET_LOCK(?, 0) AS acquired", ['legacy_stage_'.$targetName])->acquired) {
            throw new RuntimeException('Another staging operation is running.');
        }
        try {
            $result = $archive->run($source, $target, array_map(fn ($table) => $table->name, $tables), $sourceName, (bool) $this->option('write'));
        } finally {
            $target->selectOne('SELECT RELEASE_LOCK(?) AS released', ['legacy_stage_'.$targetName]);
        }
        $this->info(($this->option('write') ? 'Staged' : 'Dry-run checked').': '.$result['rows'].' original rows in '.$result['tables'].' tables.');
        $this->warn('Originals are not yet mapped to people, assessments, events, inventory or media.');
        return self::SUCCESS;
    }
}
