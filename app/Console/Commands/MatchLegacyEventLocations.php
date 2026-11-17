<?php

namespace App\Console\Commands;

use App\Support\LegacyEventLocations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchLegacyEventLocations extends Command
{
    protected $signature = 'legacy:match-event-locations {--write : Apply only unambiguous, non-conflicting matches} {--rehearsal : Use the isolated restored target}';
    protected $description = 'Match old event registry districts conservatively without replacing staff selections';

    public function handle(LegacyEventLocations $matcher): int
    {
        $targetName = $this->option('rehearsal') ? 'fathersheart2_restoretest_fd1ca66b' : 'fathersheart2';
        $sourceName = $this->option('rehearsal') ? 'fathersheart_restoretest_fd1ca66b' : 'fathersheart';
        $config = array_merge(config('database.connections.mysql'), ['url' => null, 'database' => $targetName]);
        config(['database.connections.legacy_event_locations' => $config]);
        DB::purge('legacy_event_locations');
        $db = DB::connection('legacy_event_locations');
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== $targetName) throw new RuntimeException('Unexpected target database.');
        if (! $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['legacy_event_locations_'.$targetName])->acquired) throw new RuntimeException('Another event-location match is running.');
        try {
            $this->line(json_encode($matcher->match($db, $sourceName, (bool) $this->option('write')), JSON_THROW_ON_ERROR));
            $this->warn('Matched locations are based on historical registry names and still need staff review.');
        } finally {
            $db->selectOne('SELECT RELEASE_LOCK(?) AS released', ['legacy_event_locations_'.$targetName]);
        }
        return self::SUCCESS;
    }
}
