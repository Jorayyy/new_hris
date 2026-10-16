<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'is_paid', 'pay_percentage', 'max_days_per_year', 'is_cash_convertible', 'requires_attachment', 'is_active'])]
class LeaveType extends Model
{
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_cash_convertible' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
