<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'branch_id', 'department_id', 'position_id', 'employment_type',
    'openings', 'status', 'posted_on', 'closes_on', 'description', 'requirements', 'created_by',
])]
class JobVacancy extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_ON_HOLD => 'On hold',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full time',
        'part_time' => 'Part time',
        'project' => 'Project based',
        'intern' => 'Internship',
    ];

    protected function casts(): array
    {
        return ['posted_on' => 'date', 'closes_on' => 'date', 'openings' => 'integer'];
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'green',
            self::STATUS_ON_HOLD => 'amber',
            default => 'gray',
        };
    }

    public function employmentTypeLabel(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? ucfirst($this->employment_type);
    }

    public function hiredCount(): int
    {
        return $this->applicants()->where('stage', Applicant::STAGE_HIRED)->count();
    }

    public function pendingCount(): int
    {
        return $this->applicants()->whereNotIn('stage', Applicant::TERMINAL_STAGES)->count();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
