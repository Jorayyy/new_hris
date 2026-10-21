<?php

namespace App\Models;

use App\Support\Totp;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'email', 'password', 'role', 'branch_id', 'is_active', 'otp_exempt', 'last_login_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_HR = 'hr';

    public const ROLE_PAYROLL = 'payroll';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EMPLOYEE = 'employee';

    public const HR_ROLES = [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_HR];

    public const PAYROLL_ROLES = [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_PAYROLL];

    public const MANAGEMENT_ROLES = [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_HR, self::ROLE_PAYROLL, self::ROLE_MANAGER];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'otp_exempt' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && (string) $this->two_factor_secret !== '';
    }

    public function requiresEmailOtp(): bool
    {
        return (bool) config('auth.email_otp') && (string) $this->email !== '' && ! $this->otp_exempt;
    }

    public function enableTwoFactor(): array
    {
        $secret = Totp::generateSecret();
        $codes = collect(range(1, 8))
            ->map(fn () => strtoupper(bin2hex(random_bytes(2)).'-'.bin2hex(random_bytes(2))))
            ->all();

        $this->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return ['secret' => $secret, 'codes' => $codes];
    }

    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));

        foreach ($codes as $index => $hash) {
            if (Hash::check($normalized, $hash)) {
                unset($codes[$index]);

                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function isManagement(): bool
    {
        return $this->hasRole(self::MANAGEMENT_ROLES);
    }

    public function getRoleLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->role));
    }
}
