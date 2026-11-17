<?php

namespace App\Console\Commands;

use App\Support\LegacyRecipientMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MapLegacyRecipients extends Command
{
    protected $signature = 'legacy:map-recipients {--write : Create historical people and assessments} {--rehearsal : Use isolated restored databases only}';
    protected $description = 'Verify preserved originals and map recipient rows without deduplication or inferred dates';

    public function handle(LegacyRecipientMapper $mapper): int
    {
        $rehearsal = (bool) $this->option('rehearsal');
        $sourceName = $rehearsal ? 'fathersheart_restoretest_fd1ca66b' : 'fathersheart';
        $targetName = $rehearsal ? 'fathersheart2_restoretest_fd1ca66b' : 'fathersheart2';
        $config = config('database.connections.mysql');
        $config['url'] = null;
        foreach (['legacy_map_source' => $sourceName, 'legacy_map_target' => $targetName] as $name => $database) {
            config(['database.connections.'.$name => array_merge($config, ['database' => $database])]);
            DB::purge($name);
        }
        $source = DB::connection('legacy_map_source');
        $target = DB::connection('legacy_map_target');
        if ($source->getDriverName() !== 'mysql' || $target->getDriverName() !== 'mysql'
            || $source->selectOne('SELECT DATABASE() AS db')->db !== $sourceName
            || $target->selectOne('SELECT DATABASE() AS db')->db !== $targetName) throw new RuntimeException('Unexpected database.');
        $source->statement('SET SESSION TRANSACTION READ ONLY');
        $badEngines = $target->select("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('people','assessments','distribution_events','legacy_source_records') AND ENGINE <> 'InnoDB'", [$targetName]);
        if ($badEngines) throw new RuntimeException('Target mapping tables must use InnoDB.');
        if (! $target->selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['legacy_map_'.$targetName])->acquired) throw new RuntimeException('Another mapping operation is running.');
        try {
            $all = $source->select("SELECT TABLE_NAME AS name FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", [$sourceName]);
            $recipients = $source->select("SELECT TABLE_NAME AS name FROM information_schema.columns WHERE TABLE_SCHEMA = ? AND LOWER(COLUMN_NAME) IN ('id','fullname','phone','givemobility') GROUP BY TABLE_NAME HAVING COUNT(DISTINCT LOWER(COLUMN_NAME)) = 4 ORDER BY TABLE_NAME", [$sourceName]);
            if (count($all) !== 215 || count($recipients) !== 190) throw new RuntimeException('Unexpected source schema; review before mapping.');
            $total = 0;
            foreach ($all as $table) {
                $checksums = [];
                foreach ($target->table('legacy_source_records')->where('source_database', $sourceName)->where('source_table', $table->name)->cursor() as $record) {
                    $json = Crypt::decryptString($record->raw_payload);
                    if (! hash_equals($record->checksum, hash('sha256', $json))) throw new RuntimeException('Archive checksum mismatch in '.$table->name);
                    $checksums[$record->source_key] = $record->checksum;
                }
                $seen = 0;
                foreach ($source->table($table->name)->orderBy('id')->cursor() as $row) {
                    $payload = (array) $row;
                    $key = hash('sha256', json_encode([$sourceName, $table->name, (string) $payload['id']], JSON_THROW_ON_ERROR));
                    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    if (! isset($checksums[$key]) || ! hash_equals($checksums[$key], hash('sha256', $json))) throw new RuntimeException('Source and archive differ in '.$table->name);
                    $seen++;
                }
                if ($seen !== count($checksums)) throw new RuntimeException('Source and archive counts differ in '.$table->name);
                $total += $seen;
            }
            if ($total !== $target->table('legacy_source_records')->count()) throw new RuntimeException('Unexpected archive records.');
            $this->info('Verified '.$total.' preserved source rows; checking recipient mappings.');
            $new = 0; $existing = 0;
            foreach ($recipients as $table) {
                $result = $mapper->map($target, $table->name, $table->name !== 'recipients', (bool) $this->option('write'));
                $new += $result['new']; $existing += $result['existing'];
            }
            $this->info(($this->option('write') ? 'Mapped' : 'Would map').' '.$new.' recipient entries; '.$existing.' already mapped.');
            $this->warn('Each entry is separate and needs review; old media and stock are not imported.');
        } finally {
            $target->selectOne('SELECT RELEASE_LOCK(?) AS released', ['legacy_map_'.$targetName]);
        }
        return self::SUCCESS;
    }
}
