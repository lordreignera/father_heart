<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalTeamProfile extends Model
{
    protected $fillable = ['legacy_source_record_id', 'user_id', 'name', 'legacy_username', 'phone', 'archived_at'];

    protected function casts(): array { return ['archived_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function original(): BelongsTo { return $this->belongsTo(LegacySourceRecord::class, 'legacy_source_record_id'); }
}
