<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'branch_id', 'shift_id', 'work_date', 'time_in', 'time_out',
    'break1_out', 'break1_in', 'lunch_out', 'lunch_in', 'break2_out', 'break2_in',
    'hours_worked', 'late_minutes', 'undertime_minutes', 'overtime_hours',
    'night_diff_hours', 'status', 'source', 'is_manual', 'remarks',
])]
class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_HOLIDAY = 'holiday';

    /** Maps a punch action to the attendance column it stamps, in punch order. */
    public const PUNCHES = [
        'in' => 'time_in',
        'break1_out' => 'break1_out',
        'break1_in' => 'break1_in',
        'lunch_out' => 'lunch_out',
        'lunch_in' => 'lunch_in',
        'break2_out' => 'break2_out',
        'break2_in' => 'break2_in',
        'out' => 'time_out',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'break1_out' => 'datetime',
            'break1_in' => 'datetime',
            'lunch_out' => 'datetime',
            'lunch_in' => 'datetime',
            'break2_out' => 'datetime',
            'break2_in' => 'datetime',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'night_diff_hours' => 'decimal:2',
            'is_manual' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
