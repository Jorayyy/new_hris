<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_required', 'requires_expiry', 'is_active', 'sort'])]
class DocumentType extends Model
{
    protected function casts(): array
    {
        return [
            'is_required' => 'bool',
            'requires_expiry' => 'bool',
            'is_active' => 'bool',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true)->where('is_active', true)
            ->orderBy('sort')->orderBy('name');
    }
}
