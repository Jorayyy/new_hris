<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\EmailOtp;
use Illuminate\Console\Command;

class OtpCommand extends Command
{
    protected $signature = 'hris:otp
        {email : The user email address}
        {--code= : Use a fixed code instead of a random one (testing only)}
        {--no-send : Do not email the code, only issue it}';

    protected $description = 'Issue a sign-in code for a user (support / testing)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        $code = EmailOtp::issue(
            $user,
            ip: null,
            send: ! $this->option('no-send'),
            fixedCode: $this->option('code'),
        );

        $this->info('Code for '.$user->email.': '.$code);
        $this->line('Expires in '.EmailOtp::TTL_MINUTES.' minutes.');
        $this->line($this->option('no-send') ? 'Not emailed (--no-send).' : 'Emailed to '.$user->email.'.');

        return self::SUCCESS;
    }
}
