<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReconcileLegacyRecipients extends Command
{
    protected $signature = 'legacy:reconcile-recipients';
    protected $description = 'Compare imported recipient fields and links against the encrypted historical originals';

    public function handle(): int
    {
        $db = DB::connection();
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== 'fathersheart2') throw new RuntimeException('Unexpected target database.');
        $expected = $db->table('legacy_source_records')->where('status', 'imported')->where('mapped_type', 'assessment')->count();
        $counts = ['checked' => 0, 'checksum_errors' => 0, 'identity_errors' => 0, 'field_errors' => 0, 'event_errors' => 0];
        $rows = $db->table('legacy_source_records as l')->join('assessments as a', 'l.mapped_id', '=', 'a.id')
            ->join('people as p', 'a.person_id', '=', 'p.id')->leftJoin('distribution_events as e', 'a.distribution_event_id', '=', 'e.id')
            ->where('l.status', 'imported')->where('l.mapped_type', 'assessment')
            ->select('l.id as record_id', 'l.source_table', 'l.source_row_id', 'l.checksum', 'l.raw_payload', 'p.full_name', 'p.phone',
                'a.age_years', 'a.gender', 'a.disability_story', 'a.current_mobility', 'a.not_given_reason', 'a.mobility_hopes',
                'a.has_wheelchair_explanation', 'a.ever_had_wheelchair_explanation', 'a.back_measurement', 'a.thigh_measurement', 'a.leg_measurement', 'a.hips_width', 'e.legacy_table');
        $rows->orderBy('l.id')->chunkById(200, function ($chunk) use (&$counts) {
            foreach ($chunk as $item) {
                $json = Crypt::decryptString($item->raw_payload);
                if (! hash_equals($item->checksum, hash('sha256', $json))) { $counts['checksum_errors']++; continue; }
                $row = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $counts['checked']++;
                $phone = trim((string) ($row['phone'] ?? ''));
                if ((string) ($row['id'] ?? '') !== $item->source_row_id || trim((string) ($row['fullname'] ?? '')) !== $item->full_name
                    || ($phone === '' ? null : $phone) !== $item->phone) $counts['identity_errors']++;
                $age = filter_var($row['age'] ?? null, FILTER_VALIDATE_INT);
                $age = $age !== false && $age >= 0 && $age <= 120 ? $age : null;
                $gender = strtolower(trim((string) ($row['gender'] ?? '')));
                if (! in_array($gender, ['male', 'female'], true)) $gender = null;
                $fields = [
                    'age_years' => $age, 'gender' => $gender, 'disability_story' => $row['disabilitystory'] ?? null,
                    'current_mobility' => $row['currentmobility'] ?? null, 'not_given_reason' => $row['notgiven'] ?? null,
                    'mobility_hopes' => $row['whywantchair'] ?? null, 'has_wheelchair_explanation' => $row['whyhas'] ?? null,
                    'ever_had_wheelchair_explanation' => $row['whyhad'] ?? null,
                    'back_measurement' => isset($row['back']) ? (string) $row['back'] : null,
                    'thigh_measurement' => isset($row['thigh']) ? (string) $row['thigh'] : null,
                    'leg_measurement' => isset($row['leg']) ? (string) $row['leg'] : null,
                    'hips_width' => isset($row['hips']) ? (string) $row['hips'] : null,
                ];
                foreach ($fields as $field => $value) {
                    if (($item->{$field} === null) !== ($value === null) || ($value !== null && (string) $item->{$field} !== (string) $value)) { $counts['field_errors']++; break; }
                }
                if (($item->source_table === 'recipients' && $item->legacy_table !== null)
                    || ($item->source_table !== 'recipients' && $item->legacy_table !== $item->source_table)) $counts['event_errors']++;
            }
        }, 'l.id', 'record_id');
        $counts['missing_links'] = $expected - $counts['checked'] - $counts['checksum_errors'];
        $this->line(json_encode($counts, JSON_THROW_ON_ERROR));
        if (array_sum(array_diff_key($counts, ['checked' => 0])) !== 0) {
            $this->error('Review required. Differences may also reflect subsequent staff edits; no records were changed.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
