<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyVisit extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['notes'];
    protected function casts(): array { return ['visited_on' => 'date', 'notes' => 'encrypted']; }
    public function child() { return $this->belongsTo(TherapyChild::class, 'therapy_child_id'); }
    public function services() { return $this->belongsToMany(TherapyService::class, 'therapy_visit_service'); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
