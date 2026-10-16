<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['payroll_period_id', 'branch_id', 'status', 'gross_total', 'deductions_total', 'net_total', 'employee_count', 'processed_by', 'processed_at', 'approved_by', 'approved_at', 'voided_by', 'voided_at', 'void_reason'])]
class PayrollRun extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_VOIDED = 'voided';

    protected function casts(): array
    {
        return [
            'gross_total' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'net_total' => 'decimal:2',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
