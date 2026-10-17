<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id', 'branch_id', 'filed_by', 'type', 'severity', 'status',
    'incident_date', 'subject', 'details', 'response_due_on',
    'decision', 'sanction', 'decided_by', 'decided_at',
])]
class DisciplinaryCase extends Model
{
    public const TYPE_INCIDENT = 'incident';

    public const TYPE_NTE = 'nte';

    public const TYPE_VERIFICATION = 'verification';

    public const TYPE_WARNING = 'warning';

    public const TYPE_MEMO = 'memo';

    public const TYPE_SUSPENSION = 'suspension';

    public const TYPES = [
        self::TYPE_INCIDENT => 'Incident report',
        self::TYPE_NTE => 'Notice to explain (NTE)',
        self::TYPE_VERIFICATION => 'Verification',
        self::TYPE_WARNING => 'Written warning',
        self::TYPE_MEMO => 'Memo',
        self::TYPE_SUSPENSION => 'Suspension',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_AWAITING = 'awaiting_response';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_AWAITING => 'Awaiting response',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'response_due_on' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isOpenForResponse(): bool
    {
        return $this->status === self::STATUS_AWAITING;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DisciplinaryCaseAction::class)->oldest('id');
    }
}
