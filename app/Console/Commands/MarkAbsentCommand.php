<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentCommand extends Command
{
    protected $signature = 'attendance:mark-absent {date? : The date to mark, defaults to today}';

    protected $description = 'Mark scheduled employees with no attendance as absent, skipping rest days, holidays and approved leave.';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $day = $date->toDateString();
        $count = 0;

        Employee::where('is_active', true)->chunkById(200, function ($employees) use ($date, $day, &$count) {
            foreach ($employees as $employee) {
                if ($employee->date_hired && $employee->date_hired->greaterThan($date)) {
                    continue;
                }
                if ($employee->date_separated && $employee->date_separated->lessThanOrEqualTo($date)) {
                    continue;
                }
                if (! $employee->shiftForDate($day)) {
                    continue;
                }
                if (in_array((int) $date->dayOfWeek, array_map('intval', (array) $employee->rest_days), true)) {
                    continue;
                }
                if ($this->isHoliday($employee->branch_id, $day)) {
                    continue;
                }
                if ($this->onApprovedLeave($employee->id, $day)) {
                    continue;
                }
                if (Attendance::where('employee_id', $employee->id)->whereDate('work_date', $day)->exists()) {
                    continue;
                }

                Attendance::create([
                    'employee_id' => $employee->id,
                    'branch_id' => $employee->branch_id,
                    'work_date' => $day,
                    'status' => Attendance::STATUS_ABSENT,
                    'source' => 'auto',
                ]);
                $count++;
            }
        });

        $this->info("Marked {$count} employee(s) absent for {$day}.");

        return self::SUCCESS;
    }

    private function isHoliday(?int $branchId, string $day): bool
    {
        return Holiday::query()
            ->whereDate('date', $day)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId))
            ->exists();
    }

    private function onApprovedLeave(int $employeeId, string $day): bool
    {
        return LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('date_from', '<=', $day)
            ->whereDate('date_to', '>=', $day)
            ->exists();
    }
}
