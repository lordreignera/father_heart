<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/** Copies source rows as encrypted, unmapped originals. It does not create care records. */
class LegacyArchive
{
    public function run(Connection $source, Connection $target, array $tables, string $sourceName, bool $write): array
    {
        if ($target->table('legacy_source_records')->exists()) {
            throw new RuntimeException('The archive is not empty; refusing to overwrite or duplicate existing records.');
        }

        $work = function () use ($source, $target, $tables, $sourceName, $write): array {
            $batchId = $write ? $target->table('legacy_import_batches')->insertGetId([
                'source_database' => $sourceName, 'status' => 'staging', 'created_at' => now(), 'updated_at' => now(),
            ]) : null;
            $counts = [];
            $total = 0;
            foreach ($tables as $table) {
                $expected = (int) $source->table($table)->count();
                $digests = [];
                foreach ($source->table($table)->orderBy('id')->cursor() as $row) {
                    $payload = (array) $row;
                    if (! array_key_exists('id', $payload) || $payload['id'] === null || strlen((string) $payload['id']) > 80) {
                        throw new RuntimeException('Invalid source row identity in table '.$table);
                    }
                    $id = (string) $payload['id'];
                    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    $key = hash('sha256', json_encode([$sourceName, $table, $id], JSON_THROW_ON_ERROR));
                    if (isset($digests[$key])) throw new RuntimeException('Duplicate source key in table '.$table);
                    $digest = hash('sha256', $json);
                    $digests[$key] = $digest;
                    if ($write) {
                        $target->table('legacy_source_records')->insert([
                            'legacy_import_batch_id' => $batchId, 'source_key' => $key,
                            'source_database' => $sourceName, 'source_table' => $table, 'source_row_id' => $id,
                            'checksum' => $digest, 'raw_payload' => Crypt::encryptString($json), 'status' => 'staged',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
                if (count($digests) !== $expected) throw new RuntimeException('Source count changed in table '.$table);

                // Check that the source did not change and every stored original decrypts correctly.
                $rechecked = 0;
                foreach ($source->table($table)->orderBy('id')->cursor() as $row) {
                    $payload = (array) $row;
                    $id = (string) $payload['id'];
                    $key = hash('sha256', json_encode([$sourceName, $table, $id], JSON_THROW_ON_ERROR));
                    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    if (! isset($digests[$key]) || ! hash_equals($digests[$key], hash('sha256', $json))) {
                        throw new RuntimeException('Source data changed during staging in table '.$table);
                    }
                    $rechecked++;
                }
                if ($rechecked !== $expected) throw new RuntimeException('Source count changed during verification in table '.$table);
                if ($write) {
                    $storedCount = 0;
                    foreach ($target->table('legacy_source_records')->where('legacy_import_batch_id', $batchId)->where('source_table', $table)->cursor() as $record) {
                        if (! isset($digests[$record->source_key]) || ! hash_equals($digests[$record->source_key], $record->checksum)
                            || ! hash_equals($record->checksum, hash('sha256', Crypt::decryptString($record->raw_payload)))) {
                            throw new RuntimeException('Archive verification failed in table '.$table);
                        }
                        $storedCount++;
                    }
                    if ($storedCount !== $expected) throw new RuntimeException('Archive count mismatch in table '.$table);
                }
                $counts[$table] = $expected;
                $total += $expected;
            }
            if ($write) {
                $target->table('legacy_import_batches')->where('id', $batchId)->update([
                    'status' => 'staged', 'counts' => json_encode(['tables' => count($counts), 'rows' => $total, 'table_counts' => $counts], JSON_THROW_ON_ERROR), 'updated_at' => now(),
                ]);
            }
            return ['tables' => count($counts), 'rows' => $total, 'table_counts' => $counts];
        };

        return $write ? $target->transaction($work) : $work();
    }
}
