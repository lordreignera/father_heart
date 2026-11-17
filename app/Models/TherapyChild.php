<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyChild extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['registration_data'];
    protected function casts(): array { return ['registration_data' => 'encrypted:array', 'registered_on' => 'date', 'date_of_birth' => 'date']; }
    public function person() { return $this->belongsTo(Person::class); }
    public function district() { return $this->belongsTo(District::class)->withTrashed(); }
    public function visits() { return $this->hasMany(TherapyVisit::class); }
    public function documents() { return $this->hasMany(TherapyDocument::class); }
}
