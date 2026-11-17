<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyService extends Model
{
    protected $fillable = ['name', 'category', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function visits() { return $this->belongsToMany(TherapyVisit::class, 'therapy_visit_service'); }
}
