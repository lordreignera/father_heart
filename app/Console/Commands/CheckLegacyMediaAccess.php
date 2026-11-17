<?php

namespace App\Console\Commands;

use App\Models\LegacySourceRecord;
use App\Support\LegacyMedia;
use Illuminate\Console\Command;

class CheckLegacyMediaAccess extends Command
{
    protected $signature = 'legacy:media-check';
    protected $description = 'Check whether an archived historical image can be resolved without displaying personal data';

    public function handle(LegacyMedia $media): int
    {
        foreach (LegacySourceRecord::where('mapped_type', 'assessment')->where('status', 'imported')->cursor() as $record) {
            $row = $record->raw_payload;
            if ($media->imagePath($row['beforee'] ?? null) || $media->imagePath($row['afterr'] ?? null)) {
                $this->info('A private historical image reference resolved successfully.');
                return self::SUCCESS;
            }
        }
        $this->error('No private historical image reference resolved.');
        return self::FAILURE;
    }
}
