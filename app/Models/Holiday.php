<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'date', 'name', 'type', 'rate_multiplier'])]
class Holiday extends Model
{
    public const TYPE_REGULAR = 'regular';

    public const TYPE_SPECIAL = 'special';

    protected function casts(): array
    {
        return ['date' => 'date', 'rate_multiplier' => 'decimal:2'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }
}
