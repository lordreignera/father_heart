<?php

namespace App\Console\Commands;

use App\Models\LegacySourceRecord;
use Illuminate\Console\Command;
use RuntimeException;

class AuditLegacyMedia extends Command
{
    protected $signature = 'legacy:media-audit';
    protected $description = 'Count historical image references without exposing paths or copying files';

    public function handle(): int
    {
        $root = realpath(base_path('fathersheart1/public/images'));
        if ($root === false || ! is_dir($root)) throw new RuntimeException('Historical images folder is unavailable.');
        $rootPrefix = strtolower(rtrim(str_replace('\\', '/', $root), '/').'/');
        $stats = ['empty' => 0, 'unsupported_reference' => 0, 'missing' => 0, 'outside_root' => 0, 'image_found' => 0, 'not_image' => 0];
        $found = [];
        foreach (LegacySourceRecord::where('mapped_type', 'assessment')->where('status', 'imported')->cursor() as $record) {
            $row = $record->raw_payload;
            foreach (['beforee', 'afterr'] as $field) {
                $reference = trim((string) ($row[$field] ?? ''));
                if ($reference === '' || $reference === '0') { $stats['empty']++; continue; }
                $reference = str_replace('\\', '/', $reference);
                if (! str_starts_with(strtolower($reference), 'images/') || str_contains($reference, "\0")) { $stats['unsupported_reference']++; continue; }
                $path = realpath($root.'/'.substr($reference, 7));
                if ($path === false || ! is_file($path)) { $stats['missing']++; continue; }
                if (! str_starts_with(strtolower(str_replace('\\', '/', $path)), $rootPrefix)) { $stats['outside_root']++; continue; }
                if (@getimagesize($path) === false) { $stats['not_image']++; continue; }
                $stats['image_found']++;
                $found[strtolower($path)] = true;
            }
        }
        $stats['unique_files'] = count($found);
        $this->line(json_encode($stats, JSON_THROW_ON_ERROR));
        $this->warn('Reference matches do not establish consent or prove that an old filename was not reused.');
        return self::SUCCESS;
    }
}
