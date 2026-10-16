<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['year', 'period', 'min_taxable', 'max_taxable', 'base_tax', 'rate', 'excess_over'])]
class TaxTable extends Model
{
    protected function casts(): array
    {
        return [
            'min_taxable' => 'decimal:2',
            'max_taxable' => 'decimal:2',
            'base_tax' => 'decimal:2',
            'rate' => 'decimal:4',
            'excess_over' => 'decimal:2',
        ];
    }
}
