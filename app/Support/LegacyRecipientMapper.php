<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class LegacyRecipientMapper
{
    /** Import one historical table, one original per person; never merge or overwrite. */
    public function map(Connection $db, string $sourceTable, bool $makeEvent, bool $write): array
    {
        $eventId = null;
        if ($write && $makeEvent) {
            $eventId = $db->transaction(function () use ($db, $sourceTable) {
                $event = $db->table('distribution_events')->where('legacy_table', $sourceTable)->lockForUpdate()->first();
                if ($event) return $event->id;
                return $db->table('distribution_events')->insertGetId([
                    'name' => str_replace('_', ' ', $sourceTable), 'legacy_table' => $sourceTable,
                    'status' => 'historical', 'notes' => 'Historical source table; date and event details require review.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        }
        $result = ['new' => 0, 'existing' => 0];
        $db->table('legacy_source_records')->where('source_table', $sourceTable)->orderBy('id')->chunkById(100, function ($records) use ($db, $write, $eventId, &$result) {
            $operation = function () use ($db, $write, $eventId, $records, &$result) {
                foreach ($records as $original) {
                    $record = $write ? $db->table('legacy_source_records')->where('id', $original->id)->lockForUpdate()->first() : $original;
                    $json = Crypt::decryptString($record->raw_payload);
                    if (! hash_equals($record->checksum, hash('sha256', $json))) throw new RuntimeException('Historical original checksum mismatch.');
                    $row = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    if (! is_array($row) || ! isset($row['id'], $row['fullname']) || ! array_key_exists('phone', $row)
                        || ! array_key_exists('givemobility', $row) || (string) $row['id'] !== $record->source_row_id
                        || trim((string) $row['fullname']) === '' || mb_strlen((string) $row['fullname']) > 180) {
                        throw new RuntimeException('Historical recipient needs manual review; original was preserved.');
                    }
                    if ($record->status === 'imported') {
                        if ($record->mapped_type !== 'assessment' || ! $db->table('assessments')->where('id', $record->mapped_id)->exists()) {
                            throw new RuntimeException('Existing historical mapping is inconsistent.');
                        }
                        $result['existing']++;
                        continue;
                    }
                    if ($record->status !== 'staged' || $record->mapped_id !== null || $record->mapped_type !== null) {
                        throw new RuntimeException('Unexpected historical mapping state.');
                    }
                    $phone = trim((string) ($row['phone'] ?? ''));
                    if (mb_strlen($phone) > 40) throw new RuntimeException('Historical phone exceeds the target field size.');
                    $age = filter_var($row['age'] ?? null, FILTER_VALIDATE_INT);
                    $gender = strtolower(trim((string) ($row['gender'] ?? '')));
                    if (! in_array($gender, ['male', 'female'], true)) $gender = null;
                    if ($write) {
                        $personId = $db->table('people')->insertGetId([
                            'full_name' => trim((string) $row['fullname']), 'phone' => $phone === '' ? null : $phone,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $assessmentId = $db->table('assessments')->insertGetId([
                            'person_id' => $personId, 'distribution_event_id' => $eventId, 'status' => 'legacy_unverified',
                            'age_years' => $age !== false && $age >= 0 && $age <= 120 ? $age : null, 'gender' => $gender,
                            'disability_story' => $row['disabilitystory'] ?? null, 'current_mobility' => $row['currentmobility'] ?? null,
                            'not_given_reason' => $row['notgiven'] ?? null, 'mobility_hopes' => $row['whywantchair'] ?? null,
                            'has_wheelchair_explanation' => $row['whyhas'] ?? null, 'ever_had_wheelchair_explanation' => $row['whyhad'] ?? null,
                            'back_measurement' => isset($row['back']) ? (string) $row['back'] : null,
                            'thigh_measurement' => isset($row['thigh']) ? (string) $row['thigh'] : null,
                            'leg_measurement' => isset($row['leg']) ? (string) $row['leg'] : null,
                            'hips_width' => isset($row['hips']) ? (string) $row['hips'] : null,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $updated = $db->table('legacy_source_records')->where('id', $record->id)->where('status', 'staged')->update([
                            'status' => 'imported', 'mapped_type' => 'assessment', 'mapped_id' => $assessmentId, 'updated_at' => now(),
                        ]);
                        if ($updated !== 1) throw new RuntimeException('Could not atomically map original record.');
                    }
                    $result['new']++;
                }
            };
            if ($write) $db->transaction($operation); else $operation();
        });
        return $result;
    }
}
