<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['name', 'branch_id', 'token_hash', 'is_active', 'last_seen_at'])]
class BiometricDevice extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** Generate a new plaintext token; only its hash is ever persisted. */
    public function issueToken(): string
    {
        $token = self::generateToken();
        $this->token_hash = self::hashToken($token);
        $this->save();

        return $token;
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token_hash', self::hashToken($token))->where('is_active', true)->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
