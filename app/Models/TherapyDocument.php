<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyDocument extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['path'];
    public function child() { return $this->belongsTo(TherapyChild::class, 'therapy_child_id'); }
}
