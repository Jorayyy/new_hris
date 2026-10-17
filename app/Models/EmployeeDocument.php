<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'document_type_id', 'type', 'title', 'file_path', 'expires_at', 'uploaded_by'])]
class EmployeeDocument extends Model
{
    public const EXPIRY_WARN_DAYS = 30;

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function expiryState(): ?string
    {
        $days = $this->daysUntilExpiry();

        if ($days === null) {
            return null;
        }

        return match (true) {
            $days < 0 => 'expired',
            $days <= self::EXPIRY_WARN_DAYS => 'expiring',
            default => 'ok',
        };
    }
}
