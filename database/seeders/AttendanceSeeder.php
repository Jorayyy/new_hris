<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $dtr = new DtrService;
        $employees = Employee::all();
        $start = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $today = Carbon::now();

        foreach ($employees as $employee) {
            $shift = $employee->shiftForDate($today) ?? Shift::first();
            $cursor = $start->copy();

            while ($cursor->lessThanOrEqualTo($today)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();

                    continue;
                }

                $day = $cursor->copy();
                $timeIn = $day->copy()->setTimeFromTimeString($shift?->time_in ?? '08:00');
                if (random_int(0, 100) < 25) {
                    $timeIn->addMinutes(random_int(5, 25));
                }

                $timeOut = $day->copy()->setTimeFromTimeString($shift?->time_out ?? '17:00');
                if (($shift?->time_out ?? '17:00') <= ($shift?->time_in ?? '08:00')) {
                    $timeOut->addDay();
                }
                if (random_int(0, 100) < 20) {
                    $timeOut->addMinutes(random_int(15, 90));
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'branch_id' => $employee->branch_id,
                    'shift_id' => $shift?->id,
                    'work_date' => $day->toDateString(),
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'status' => Attendance::STATUS_PRESENT,
                    'source' => 'biometric',
                ]);

                $dtr->process($attendance);

                $cursor->addDay();
            }
        }
    }
}
