<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['min_msc', 'max_msc', 'employee_share', 'employer_share', 'ec_share'])]
class SssContribution extends Model
{
    protected function casts(): array
    {
        return [
            'min_msc' => 'decimal:2',
            'max_msc' => 'decimal:2',
            'employee_share' => 'decimal:2',
            'employer_share' => 'decimal:2',
            'ec_share' => 'decimal:2',
        ];
    }
}
