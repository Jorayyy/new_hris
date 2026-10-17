<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'weight', 'is_active', 'sort'])]
class QaCriterion extends Model
{
    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'is_active' => 'bool'];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(QaEvaluationScore::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }
}
