<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalFeedbackProfile extends Model
{
    protected $fillable = ['legacy_source_record_id', 'staff', 'recipient', 'rating', 'archived_at'];

    protected function casts(): array
    {
        return ['staff' => 'encrypted', 'recipient' => 'encrypted', 'rating' => 'encrypted', 'archived_at' => 'datetime'];
    }
}
