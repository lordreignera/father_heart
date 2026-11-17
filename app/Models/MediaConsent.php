<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaConsent extends Model
{
    protected $fillable = ['person_id', 'assessment_id', 'status', 'signed_on', 'signer_name', 'guardian_name', 'guardian_signed_on', 'document_path', 'form_version', 'recipient_identity', 'recipient_phone'];

    protected $hidden = ['document_path', 'recipient_identity'];

    protected function casts(): array
    {
        return ['signed_on' => 'date', 'guardian_signed_on' => 'date', 'recipient_identity' => 'encrypted'];
    }

    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
