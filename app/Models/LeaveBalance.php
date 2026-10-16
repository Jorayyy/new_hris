<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'leave_type_id', 'year', 'credits', 'used'])]
class LeaveBalance extends Model
{
    protected function casts(): array
    {
        return ['credits' => 'decimal:2', 'used' => 'decimal:2'];
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->credits - (float) $this->used, 2);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
