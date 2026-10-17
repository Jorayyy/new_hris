<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'job_vacancy_id', 'first_name', 'last_name', 'email', 'contact_no',
    'source', 'stage', 'applied_on', 'notes', 'employee_id',
])]
class Applicant extends Model
{
    public const STAGE_APPLIED = 'applied';

    public const STAGE_SCREENING = 'screening';

    public const STAGE_INTERVIEW = 'interview';

    public const STAGE_OFFER = 'offer';

    public const STAGE_HIRED = 'hired';

    public const STAGE_REJECTED = 'rejected';

    public const STAGE_WITHDRAWN = 'withdrawn';

    public const STAGES = [
        self::STAGE_APPLIED => 'Applied',
        self::STAGE_SCREENING => 'Screening',
        self::STAGE_INTERVIEW => 'Interview',
        self::STAGE_OFFER => 'Offer',
        self::STAGE_HIRED => 'Hired',
        self::STAGE_REJECTED => 'Rejected',
        self::STAGE_WITHDRAWN => 'Withdrawn',
    ];

    public const SOURCES = ['Walk-in', 'Referral', 'Job board', 'Agency', 'Social media', 'School'];

    public const TERMINAL_STAGES = [self::STAGE_HIRED, self::STAGE_REJECTED, self::STAGE_WITHDRAWN];

    protected function casts(): array
    {
        return ['applied_on' => 'date'];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ApplicantActivity::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isHired(): bool
    {
        return $this->stage === self::STAGE_HIRED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->stage, self::TERMINAL_STAGES, true);
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst($this->stage);
    }

    public function stageTone(): string
    {
        return match ($this->stage) {
            self::STAGE_HIRED => 'green',
            self::STAGE_INTERVIEW => 'amber',
            self::STAGE_OFFER => 'cyan',
            self::STAGE_SCREENING => 'blue',
            self::STAGE_REJECTED => 'red',
            default => 'gray',
        };
    }

    public function logActivity(string $type, ?string $notes = null): ApplicantActivity
    {
        return $this->activities()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'stage' => $this->stage,
            'notes' => $notes,
        ]);
    }
}
