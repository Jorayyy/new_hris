<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Console\Command;

class TwoFactorCommand extends Command
{
    protected $signature = 'hris:2fa
        {action : enable, disable or status}
        {email : The user email address}
        {--force : Re-provision even if 2FA is already enabled}';

    protected $description = 'Manage TOTP two-factor authentication for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'enable' => $this->enable($user),
            'disable' => $this->disable($user),
            'status' => $this->status($user),
            default => $this->invalid(),
        };
    }

    private function enable(User $user): int
    {
        if ($user->hasTwoFactorEnabled() && ! $this->option('force')) {
            $this->warn('2FA is already enabled. Use --force to re-provision (invalidates the old authenticator).');

            return self::FAILURE;
        }

        $result = $user->enableTwoFactor();

        $this->info('2FA enabled for '.$user->email);
        $this->line('Secret: '.$result['secret']);
        $this->line('otpauth URI: '.Totp::uri($result['secret'], $user->email, config('app.name')));
        $this->line('Recovery codes (each single-use):');

        foreach ($result['codes'] as $code) {
            $this->line('  '.$code);
        }

        return self::SUCCESS;
    }

    private function disable(User $user): int
    {
        $user->disableTwoFactor();
        $this->info('2FA disabled for '.$user->email);

        return self::SUCCESS;
    }

    private function status(User $user): int
    {
        $this->line($user->email.' ('.$user->role.'): '.($user->hasTwoFactorEnabled() ? '2FA enabled' : '2FA not enabled'));
        $this->line('Recovery codes left: '.count($user->two_factor_recovery_codes ?? []));

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Unknown action. Use enable, disable or status.');

        return self::FAILURE;
    }
}
