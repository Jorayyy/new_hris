<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'interest_rate', 'max_amount', 'is_active'])]
class LoanType extends Model
{
    protected function casts(): array
    {
        return ['interest_rate' => 'decimal:4', 'max_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }
}
