<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $fillable = ['full_name', 'phone', 'nin', 'created_by'];

    protected $hidden = ['nin'];

    protected function casts(): array
    {
        return ['nin' => 'encrypted'];
    }

    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function mediaConsents(): HasMany { return $this->hasMany(MediaConsent::class); }
    public function therapyChild() { return $this->hasOne(TherapyChild::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
