<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

#[Fillable([
    'employee_no', 'pin', 'user_id', 'branch_id', 'department_id', 'position_id',
    'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'gender',
    'civil_status', 'nationality', 'address', 'contact_no', 'personal_email',
    'date_hired', 'date_regularized', 'date_separated', 'employment_status', 'separation_reason', 'probation_months',
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

    public const REGULARIZATION_WARN_DAYS = 15;

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
            'probation_months' => 'integer',
            'pin' => 'hashed',
            'pin_locked_until' => 'datetime',
            'rest_days' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->isDirty('pin')) {
                $employee->pin_fast = null;
            }
        });
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until !== null && $this->pin_locked_until->isFuture();
    }

    public function checkPin(string $pin): bool
    {
        $fast = hash_hmac('sha256', $pin, (string) config('app.key'));

        if (is_string($this->pin_fast) && $this->pin_fast !== '' && hash_equals($this->pin_fast, $fast)) {
            return true;
        }

        if (! $this->pin || ! Hash::check($pin, $this->pin)) {
            return false;
        }

        if (Hash::needsRehash($this->pin)) {
            $this->forceFill(['pin' => Hash::make($pin)])->save();
        }

        $this->forceFill(['pin_fast' => $fast])->save();

        return true;
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

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
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

    public function documentCompliance(): Collection
    {
        $documents = $this->documents->keyBy('document_type_id');

        return DocumentType::query()->required()->get()->map(function (DocumentType $type) use ($documents) {
            $document = $documents->get($type->id);

            return [
                'type' => $type,
                'document' => $document,
                'state' => $document ? ($document->expiryState() ?? 'ok') : 'missing',
            ];
        });
    }

    public function documentIssueCount(): int
    {
        return $this->documentCompliance()->reject(fn (array $row) => $row['state'] === 'ok')->count();
    }

    public const CLEARANCE_ITEMS = [
        'Return company ID',
        'Return uniforms and PPE',
        'Return laptop, tools and equipment',
        'Return keys, locker and access cards',
        'Clear department accountabilities',
        'Clear cash advances and loans',
        'Settle final pay computation',
        'Conduct exit interview',
        'Release last pay and Certificate of Employment',
        'Deactivate email and system accounts',
        'Revoke time-clock PIN and door access',
    ];

    public function clearanceItems(): HasMany
    {
        return $this->hasMany(ClearanceItem::class)->orderBy('sort')->orderBy('id');
    }

    public function qaEvaluations(): HasMany
    {
        return $this->hasMany(QaEvaluation::class);
    }

    public function regularizationDueOn(): ?Carbon
    {
        return $this->date_hired?->copy()->addMonths($this->probation_months ?: 6);
    }

    public function regularizationDaysLeft(): ?int
    {
        $due = $this->regularizationDueOn();

        return $due ? (int) now()->startOfDay()->diffInDays($due, false) : null;
    }

    public function isForRegularization(): bool
    {
        return $this->is_active
            && $this->employment_status === 'probationary'
            && $this->regularizationDueOn() !== null;
    }

    public function startClearance(): int
    {
        $existing = $this->clearanceItems()->pluck('label')->all();
        $created = 0;

        foreach (self::CLEARANCE_ITEMS as $sort => $label) {
            if (in_array($label, $existing, true)) {
                continue;
            }

            $this->clearanceItems()->create(['label' => $label, 'sort' => $sort]);
            $created++;
        }

        return $created;
    }

    public function clearanceProgress(): array
    {
        return [
            'done' => $this->clearanceItems->where('is_done', true)->count(),
            'total' => $this->clearanceItems->count(),
        ];
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
