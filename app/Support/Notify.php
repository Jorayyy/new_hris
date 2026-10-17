<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\HrisNotification;
use Illuminate\Support\Facades\Notification;

class Notify
{
    public static function approvers(string $title, string $body, ?string $url = null, string $icon = '•'): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereIn('role', [...User::HR_ROLES, User::ROLE_MANAGER])
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new HrisNotification($title, $body, $url, $icon));
        }
    }

    public static function employee(?Employee $employee, string $title, string $body, ?string $url = null, string $icon = '•'): void
    {
        $employee?->user?->notify(new HrisNotification($title, $body, $url, $icon));
    }
}
