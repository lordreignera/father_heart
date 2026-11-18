<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestimonyVideo extends Model
{
    protected $fillable = ['title', 'description', 'file_path', 'mime_type', 'file_size', 'uploaded_by'];
    protected $hidden = ['file_path'];
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
