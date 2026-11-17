<?php

namespace App\Console\Commands;

use App\Models\LegacySourceRecord;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuditAlternateLegacyMedia extends Command
{
    protected $signature = 'legacy:alternate-media-audit';
    protected $description = 'Check whether missing historical photo paths exist in alternate archives without linking them';

    public function handle(): int
    {
        $folders = [base_path('fathersheart1/public/images'), base_path('fathersheart1/public/Adrienne1/most recent/images'), base_path('fathersheart1/public/test.fathersheartmobility.com/images')];
        $sets = [];
        foreach ($folders as $folder) {
            $root = realpath($folder);
            if (! $root) { $sets[] = []; continue; }
            $set = [];
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && ! $file->isLink()) $set[strtolower(str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)))] = true;
            }
            $sets[] = $set;
        }
        $references = 0; $unique = [];
        foreach (LegacySourceRecord::where('mapped_type', 'assessment')->where('status', 'imported')->cursor() as $record) {
            foreach (['beforee', 'afterr'] as $field) {
                $reference = strtolower(str_replace('\\', '/', trim((string) ($record->raw_payload[$field] ?? ''))));
                if (! str_starts_with($reference, 'images/') || str_contains($reference, '../')) continue;
                $key = substr($reference, 7);
                if (! isset($sets[0][$key]) && (isset($sets[1][$key]) || isset($sets[2][$key]))) { $references++; $unique[$key] = true; }
            }
        }
        $this->line('Alternate filename matches for missing primary references: '.$references.' references, '.count($unique).' unique names.');
        $this->warn('Matching filenames alone do not prove a file belongs to a person; no links were created.');
        return self::SUCCESS;
    }
}
