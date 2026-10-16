<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payroll_run_id', 'employee_id', 'days_worked', 'hours_worked',
    'basic_pay', 'overtime_pay', 'night_diff_pay', 'holiday_pay', 'rest_day_pay',
    'leave_pay', 'allowances_total', 'gross_pay',
    'sss_contribution', 'philhealth_contribution', 'pagibig_contribution', 'tax_withheld',
    'late_undertime_deduction', 'other_deductions_total',
    'total_deductions', 'net_pay', 'status',
])]
class Payslip extends Model
{
    protected function casts(): array
    {
        return [
            'days_worked' => 'decimal:2',
            'hours_worked' => 'decimal:2',
            'basic_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'night_diff_pay' => 'decimal:2',
            'holiday_pay' => 'decimal:2',
            'rest_day_pay' => 'decimal:2',
            'leave_pay' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'sss_contribution' => 'decimal:2',
            'philhealth_contribution' => 'decimal:2',
            'pagibig_contribution' => 'decimal:2',
            'tax_withheld' => 'decimal:2',
            'late_undertime_deduction' => 'decimal:2',
            'other_deductions_total' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }
}
