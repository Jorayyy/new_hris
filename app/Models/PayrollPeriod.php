<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['branch_id', 'name', 'date_from', 'date_to', 'pay_date', 'frequency', 'status'])]
class PayrollPeriod extends Model
{
    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date', 'pay_date' => 'date'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }
}
