<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = ['name', 'unit'];

    protected function casts(): array { return ['opening_date' => 'date']; }

    public function movements(): HasMany { return $this->hasMany(InventoryMovement::class); }
}
