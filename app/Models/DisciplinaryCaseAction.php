<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['disciplinary_case_id', 'user_id', 'type', 'body', 'file_path'])]
class DisciplinaryCaseAction extends Model
{
    public const TYPE_FILED = 'filed';

    public const TYPE_NOTE = 'note';

    public const TYPE_RESPONSE = 'response';

    public const TYPE_DECISION = 'decision';

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
