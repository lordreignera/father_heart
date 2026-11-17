<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyOption extends Model
{
    protected $fillable = ['category', 'name', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
