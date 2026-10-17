<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\HrisNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class Notify
{
    /**
     * HR company-wide plus managers of the employee's branch.
     */
    public static function approvers(string $title, string $body, ?string $url = null, string $icon = '•', ?Employee $about = null): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->whereIn('role', User::HR_ROLES)
                ->when($about?->branch_id, fn ($w) => $w->orWhere(fn ($m) => $m->where('role', User::ROLE_MANAGER)->where('branch_id', $about->branch_id))))
            ->get();

        self::send($recipients, $title, $body, $url, $icon);
    }

    /**
     * HR roles only — for queues managers cannot open.
     */
    public static function hr(string $title, string $body, ?string $url = null, string $icon = '•'): void
    {
        self::send(
            User::query()->where('is_active', true)->whereIn('role', User::HR_ROLES)->get(),
            $title, $body, $url, $icon,
        );
    }

    public static function employee(?Employee $employee, string $title, string $body, ?string $url = null, string $icon = '•'): void
    {
        $employee?->user?->notify(new HrisNotification($title, $body, $url, $icon, mail: true));
    }

    private static function send(Collection $recipients, string $title, string $body, ?string $url, string $icon): void
    {
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new HrisNotification($title, $body, $url, $icon));
        }
    }
}
