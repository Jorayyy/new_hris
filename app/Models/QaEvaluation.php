<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id', 'branch_id', 'evaluator_id', 'evaluated_on',
    'reference', 'score', 'remarks', 'coaching_notes',
])]
class QaEvaluation extends Model
{
    public const DEFAULT_PASSING_SCORE = 85.0;

    protected function casts(): array
    {
        return ['evaluated_on' => 'date', 'score' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(QaEvaluationScore::class);
    }

    public static function passingScore(): float
    {
        return (float) Setting::get('qa_passing_score', self::DEFAULT_PASSING_SCORE);
    }

    /**
     * @param  array<int|string, int|float|string>  $scores  criterion id => score
     */
    public static function computeScore(array $scores): ?float
    {
        $criteria = QaCriterion::query()->whereIn('id', array_keys($scores))->get();
        $weight = (float) $criteria->sum('weight');

        if ($weight <= 0) {
            return null;
        }

        $weighted = $criteria->sum(fn (QaCriterion $criterion) => (float) $scores[$criterion->id] * (float) $criterion->weight);

        return round($weighted / $weight, 2);
    }

    public function isPassing(): bool
    {
        return $this->score !== null && (float) $this->score >= self::passingScore();
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->score === null => 'Not scored',
            (float) $this->score >= 95 => 'Excellent',
            $this->isPassing() => 'Passing',
            default => 'Needs coaching',
        };
    }

    public function statusTone(): string
    {
        return match (true) {
            $this->score === null => 'gray',
            (float) $this->score >= 95 => 'green',
            $this->isPassing() => 'blue',
            default => 'red',
        };
    }
}
