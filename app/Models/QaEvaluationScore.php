<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['qa_evaluation_id', 'qa_criterion_id', 'score'])]
class QaEvaluationScore extends Model
{
    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(QaEvaluation::class, 'qa_evaluation_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(QaCriterion::class, 'qa_criterion_id');
    }
}
