<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRegistrationRequest extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['password_hash'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
}
