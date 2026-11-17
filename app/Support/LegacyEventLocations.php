<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class LegacyEventLocations
{
    public static function normalize(?string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name ?? '')));
    }

    /** Historical registry values remain suggestions, not verified event locations. */
    public function registryDistricts(Connection $db, string $sourceName = 'fathersheart'): array
    {
        $groups = [];
        foreach ($db->table('legacy_source_records')->where('source_database', $sourceName)->where('source_table', 'distribution')->cursor() as $record) {
            $json = Crypt::decryptString($record->raw_payload);
            if (! hash_equals($record->checksum, hash('sha256', $json))) throw new RuntimeException('Historical registry checksum mismatch.');
            $row = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $event = self::normalize($row['distribution'] ?? null);
            $district = trim((string) ($row['district'] ?? ''));
            if ($event !== '' && $district !== '') $groups[$event][self::normalize($district)] = $district;
        }
        return $groups;
    }

    public function match(Connection $db, string $sourceName, bool $write): array
    {
        $registry = $this->registryDistricts($db, $sourceName);
        $districts = [];
        foreach ($db->table('districts')->whereNull('deleted_at')->where('is_active', true)->get(['id', 'name']) as $district) {
            $districts[self::normalize($district->name)][] = $district->id;
        }
        $stats = ['matched' => 0, 'no_registry' => 0, 'conflicting_registry' => 0, 'district_unmatched_or_ambiguous' => 0, 'recipient_conflict' => 0, 'existing_location_skipped' => 0];
        foreach ($db->table('distribution_events')->whereNotNull('legacy_table')->orderBy('id')->get() as $event) {
            $work = function () use ($db, $event, $registry, $districts, $write, &$stats) {
                $current = $write ? $db->table('distribution_events')->where('id', $event->id)->lockForUpdate()->first() : $event;
                if (! $current) throw new RuntimeException('Historical event disappeared during matching.');
                if ($current->district_id !== null || $current->subcounty_id !== null) { $stats['existing_location_skipped']++; return; }
                $names = $registry[self::normalize($current->legacy_table)] ?? [];
                if (! $names) { $stats['no_registry']++; return; }
                if (count($names) !== 1) { $stats['conflicting_registry']++; return; }
                $matches = $districts[array_key_first($names)] ?? [];
                if (count($matches) !== 1) { $stats['district_unmatched_or_ambiguous']++; return; }
                $districtId = $matches[0];
                if ($db->table('assessments')->where('distribution_event_id', $current->id)->whereNotNull('district_id')->where('district_id', '<>', $districtId)->exists()) {
                    $stats['recipient_conflict']++; return;
                }
                if ($write) $db->table('distribution_events')->where('id', $current->id)->whereNull('district_id')->whereNull('subcounty_id')->update(['district_id' => $districtId, 'updated_at' => now()]);
                $stats['matched']++;
            };
            if ($write) $db->transaction($work); else $work();
        }
        return $stats;
    }
}
