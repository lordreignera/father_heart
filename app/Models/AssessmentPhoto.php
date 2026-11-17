<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentPhoto extends Model
{
    protected $fillable = ['assessment_id', 'uploaded_by', 'kind', 'path'];

    protected $hidden = ['path'];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
