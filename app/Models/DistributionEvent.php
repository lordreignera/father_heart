<?php

namespace App\Models;

use App\Support\LegacyEventPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionEvent extends Model
{
    protected $fillable = ['name', 'event_date', 'district_id', 'subcounty_id', 'status', 'notes'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function subcounty(): BelongsTo { return $this->belongsTo(Subcounty::class); }
    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function getLegacyPeriodAttribute(): ?string { return LegacyEventPeriod::describe($this->legacy_table); }
}
