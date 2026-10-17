<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Managers are limited to employees in their own branch (users.branch_id).
 * HR, admins, super admins and payroll are unrestricted. A manager with no
 * branch is limited to nothing.
 */
class TeamScope
{
    public static function limited(User $user): bool
    {
        return $user->hasRole(User::ROLE_MANAGER);
    }

    /** Restrict a branch_id column. Unrestricted users pass through. */
    public static function apply(Builder $query, User $user, string $column = 'branch_id'): Builder
    {
        if (! self::limited($user)) {
            return $query;
        }

        return $query->where($column, $user->branch_id ?? 0);
    }

    /** Restrict an employee_id column. Unrestricted users pass through. */
    public static function applyEmployee(Builder $query, User $user, string $column = 'employee_id'): Builder
    {
        if (! self::limited($user)) {
            return $query;
        }

        return $query->whereIn($column, self::employeeIds($user));
    }

    /** @return array<int, int> */
    public static function employeeIds(User $user): array
    {
        return Employee::query()->when(self::limited($user), fn ($q) => $q->where('branch_id', $user->branch_id ?? 0))->pluck('id')->all();
    }

    public static function employees(User $user, bool $activeOnly = true): Collection
    {
        return Employee::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->when(self::limited($user), fn ($q) => $q->where('branch_id', $user->branch_id ?? 0))
            ->orderBy('last_name')
            ->get();
    }

    public static function branches(User $user): Collection
    {
        return Branch::query()
            ->when(self::limited($user), fn ($q) => $q->where('id', $user->branch_id ?? 0))
            ->orderBy('name')
            ->get();
    }

    public static function authorize(User $user, int|string|null $branchId): void
    {
        if (! self::limited($user)) {
            return;
        }

        abort_unless($user->branch_id !== null && (int) $branchId === (int) $user->branch_id, 403, 'This record is outside your branch.');
    }

    public static function authorizeEmployee(User $user, ?Employee $employee): void
    {
        self::authorize($user, $employee?->branch_id);
    }
}
