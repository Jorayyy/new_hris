<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['min_salary', 'max_salary', 'employee_rate', 'employer_rate', 'employee_cap', 'employer_cap'])]
class PagibigContribution extends Model
{
    protected function casts(): array
    {
        return [
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'employee_cap' => 'decimal:2',
            'employer_cap' => 'decimal:2',
        ];
    }
}
