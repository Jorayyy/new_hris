<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'work_date', 'time_in', 'break1_out', 'break1_in',
    'lunch_out', 'lunch_in', 'break2_out', 'break2_in', 'time_out',
    'reason', 'status', 'decided_by', 'decided_at', 'decision_remarks',
])]
class AttendanceCorrection extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** Attendance columns a correction may overwrite, in punch order. */
    public const PUNCHES = [
        'time_in', 'break1_out', 'break1_in', 'lunch_out',
        'lunch_in', 'break2_out', 'break2_in', 'time_out',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'time_in' => 'datetime',
            'break1_out' => 'datetime',
            'break1_in' => 'datetime',
            'lunch_out' => 'datetime',
            'lunch_in' => 'datetime',
            'break2_out' => 'datetime',
            'break2_in' => 'datetime',
            'time_out' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
