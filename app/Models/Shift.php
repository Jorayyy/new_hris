<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'time_in', 'time_out', 'break_minutes', 'grace_minutes', 'required_hours', 'is_night_diff', 'is_active'])]
class Shift extends Model
{
    protected function casts(): array
    {
        return [
            'required_hours' => 'decimal:2',
            'is_night_diff' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function crossesMidnight(): bool
    {
        return $this->time_out <= $this->time_in;
    }
}
