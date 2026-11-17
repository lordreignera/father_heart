<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegacySourceRecord extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['raw_payload'];

    protected function casts(): array { return ['raw_payload' => 'encrypted:array']; }

    public function batch(): BelongsTo { return $this->belongsTo(LegacyImportBatch::class, 'legacy_import_batch_id'); }
    public function teamProfile(): HasOne { return $this->hasOne(HistoricalTeamProfile::class, 'legacy_source_record_id'); }
    public function feedbackProfile(): HasOne { return $this->hasOne(HistoricalFeedbackProfile::class, 'legacy_source_record_id'); }
}
