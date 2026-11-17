<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyImportBatch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['counts' => 'array']; }
    public function records(): HasMany { return $this->hasMany(LegacySourceRecord::class); }
}
