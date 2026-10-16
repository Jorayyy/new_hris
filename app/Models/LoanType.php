<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'interest_rate', 'is_government', 'is_active'])]
class LoanType extends Model
{
    protected function casts(): array
    {
        return [
            'interest_rate' => 'decimal:4',
            'is_government' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }
}
