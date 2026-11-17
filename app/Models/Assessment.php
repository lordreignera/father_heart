<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assessment extends Model
{
    protected $guarded = ['id', 'legacy_followup'];

    protected function casts(): array
    {
        return [
            'assessed_on' => 'date',
            'causes' => 'array', 'diagnoses' => 'array',
            'has_wheelchair' => 'boolean', 'ever_had_wheelchair' => 'boolean',
            'registration_complete' => 'boolean', 'assessment_complete' => 'boolean',
            'safety_lesson_received' => 'boolean', 'therapy_received' => 'boolean',
            'released_to_go' => 'boolean', 'legacy_followup' => 'boolean',
        ];
    }

    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function distributionEvent(): BelongsTo { return $this->belongsTo(DistributionEvent::class); }
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function subcounty(): BelongsTo { return $this->belongsTo(Subcounty::class); }
    public function assessor(): BelongsTo { return $this->belongsTo(User::class, 'assessed_by'); }
    public function mediaConsents(): HasMany { return $this->hasMany(MediaConsent::class); }
    public function photos(): HasMany { return $this->hasMany(AssessmentPhoto::class); }
    public function legacySource(): HasOne { return $this->hasOne(LegacySourceRecord::class, 'mapped_id')->where('mapped_type', 'assessment'); }
}
