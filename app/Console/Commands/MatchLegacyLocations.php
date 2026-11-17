<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchLegacyLocations extends Command
{
    protected $signature = 'legacy:match-locations {--write : Apply only unambiguous exact matches} {--rehearsal : Use the isolated restored target}';
    protected $description = 'Match historical location names only when exact and unique, without replacing staff choices';

    private function normalize(?string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name ?? '')));
    }

    public function handle(): int
    {
        $targetName = $this->option('rehearsal') ? 'fathersheart2_restoretest_fd1ca66b' : 'fathersheart2';
        $config = array_merge(config('database.connections.mysql'), ['url' => null, 'database' => $targetName]);
        config(['database.connections.legacy_locations' => $config]);
        DB::purge('legacy_locations');
        $db = DB::connection('legacy_locations');
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== $targetName) throw new RuntimeException('Unexpected target database.');
        if (! $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['legacy_locations_'.$targetName])->acquired) throw new RuntimeException('Another location match is running.');
        try {
            $districts = [];
            foreach ($db->table('districts')->whereNull('deleted_at')->where('is_active', true)->get(['id', 'name']) as $district) {
                $districts[$this->normalize($district->name)][] = $district->id;
            }
            $subcounties = [];
            foreach ($db->table('subcounties')->whereNull('deleted_at')->where('is_active', true)->get(['id', 'district_id', 'name']) as $subcounty) {
                $subcounties[$subcounty->district_id][$this->normalize($subcounty->name)][] = $subcounty->id;
            }
            $stats = ['district_match' => 0, 'district_unmatched_or_ambiguous' => 0, 'subcounty_match' => 0, 'subcounty_unmatched_or_ambiguous' => 0, 'existing_location_skipped' => 0];
            $db->table('legacy_source_records')->where('status', 'imported')->where('mapped_type', 'assessment')->orderBy('id')->chunkById(100, function ($records) use ($db, $districts, $subcounties, &$stats) {
                $work = function () use ($db, $districts, $subcounties, $records, &$stats) {
                    foreach ($records as $record) {
                        $assessment = $db->table('assessments')->where('id', $record->mapped_id)->lockForUpdate()->first();
                        if (! $assessment) throw new RuntimeException('Missing mapped assessment.');
                        if ($assessment->district_id !== null || $assessment->subcounty_id !== null) { $stats['existing_location_skipped']++; continue; }
                        $json = Crypt::decryptString($record->raw_payload);
                        if (! hash_equals($record->checksum, hash('sha256', $json))) throw new RuntimeException('Historical original checksum mismatch.');
                        $raw = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                        $districtName = $this->normalize($raw['district'] ?? null);
                        $candidates = $districts[$districtName] ?? [];
                        if ($districtName === '' || count($candidates) !== 1) { $stats['district_unmatched_or_ambiguous']++; continue; }
                        $districtId = $candidates[0];
                        $stats['district_match']++;
                        $subName = $this->normalize($raw['subcounty'] ?? null);
                        $subCandidates = $subcounties[$districtId][$subName] ?? [];
                        $subId = null;
                        if ($subName !== '') {
                            if (count($subCandidates) === 1) { $subId = $subCandidates[0]; $stats['subcounty_match']++; }
                            else $stats['subcounty_unmatched_or_ambiguous']++;
                        }
                        if ($this->option('write')) $db->table('assessments')->where('id', $assessment->id)->update(['district_id' => $districtId, 'subcounty_id' => $subId, 'updated_at' => now()]);
                    }
                };
                if ($this->option('write')) $db->transaction($work); else $work();
            });
            $this->line(json_encode($stats, JSON_THROW_ON_ERROR));
            $this->warn('Original names remain visible; unmatched and ambiguous values require staff review.');
        } finally {
            $db->selectOne('SELECT RELEASE_LOCK(?) AS released', ['legacy_locations_'.$targetName]);
        }
        return self::SUCCESS;
    }
}
