<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'loan_type_id', 'principal', 'terms_months', 'monthly_amortization', 'balance', 'start_date', 'status'])]
class EmployeeLoan extends Model
{
    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'monthly_amortization' => 'decimal:2',
            'balance' => 'decimal:2',
            'start_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}
