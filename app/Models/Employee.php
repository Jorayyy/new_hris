<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_no', 'pin', 'user_id', 'branch_id', 'department_id', 'position_id',
    'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'gender',
    'civil_status', 'nationality', 'address', 'contact_no', 'personal_email',
    'date_hired', 'date_regularized', 'date_separated', 'employment_status', 'separation_reason',
    'rest_days',
    'sss_no', 'philhealth_no', 'pagibig_no', 'tin',
    'bank_name', 'bank_account_no',
    'basic_salary', 'monthly_allowance', 'daily_rate', 'hourly_rate',
    'tax_status', 'dependents_count', 'photo_path', 'is_active',
])]
class Employee extends Model
{
    public const PIN_MAX_ATTEMPTS = 5;

    public const PIN_LOCKOUT_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'date_hired' => 'date',
            'date_regularized' => 'date',
            'date_separated' => 'date',
            'basic_salary' => 'decimal:2',
            'monthly_allowance' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'pin' => 'hashed',
            'pin_locked_until' => 'datetime',
            'rest_days' => 'array',
        ];
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until !== null && $this->pin_locked_until->isFuture();
    }

    public function registerFailedPin(): void
    {
        $this->pin_attempts = (int) $this->pin_attempts + 1;

        if ($this->pin_attempts >= self::PIN_MAX_ATTEMPTS) {
            $this->pin_attempts = 0;
            $this->pin_locked_until = now()->addMinutes(self::PIN_LOCKOUT_MINUTES);
        }

        $this->save();
    }

    public function clearPinLock(): void
    {
        $this->forceFill(['pin_attempts' => 0, 'pin_locked_until' => null])->save();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.($this->middle_name ? mb_substr($this->middle_name, 0, 1).'. ' : '').$this->last_name.($this->suffix ? ' '.$this->suffix : ''));
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function shiftSwapRequests(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requester_id');
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function scheduleChangeRequests(): HasMany
    {
        return $this->hasMany(ScheduleChangeRequest::class);
    }

    public function shiftForDate(\DateTimeInterface|string $date): ?Shift
    {
        $date = is_string($date) ? $date : $date->format('Y-m-d');

        return EmployeeShift::query()
            ->where('employee_id', $this->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->latest('effective_from')
            ->first()?->shift;
    }

    public function dailyRate(): float
    {
        if ((float) $this->daily_rate > 0) {
            return (float) $this->daily_rate;
        }

        return round((float) $this->basic_salary / 22, 4);
    }

    public function hourlyRate(): float
    {
        if ((float) $this->hourly_rate > 0) {
            return (float) $this->hourly_rate;
        }

        return round($this->dailyRate() / 8, 4);
    }
}
