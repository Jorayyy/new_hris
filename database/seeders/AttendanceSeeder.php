<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Six months of weekdays-only graveyard attendance for the Tacloban roster.
 * Mixes present/late/undertime/overtime, approved leave, absences and
 * forgotten punch-outs so DTR and exception screens have real data.
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $dtr = new DtrService;
        $start = Carbon::now()->subMonths(6)->startOfDay();
        $today = Carbon::now();
        $leaveTypes = LeaveType::whereIn('code', ['VL', 'SL'])->get();

        $holidays = Holiday::all()
            ->mapWithKeys(fn (Holiday $h) => [$h->date->toDateString() => $h->type]);

        $this->command?->info('AttendanceSeeder: '.$start->toDateString().' to '.$today->toDateString().'...');

        $employees = Employee::all();
        $shiftCache = [];

        foreach ($employees as $employee) {
            $cursor = $start->copy();

            while ($cursor->lessThanOrEqualTo($today)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();

                    continue;
                }

                $date = $cursor->toDateString();
                $shift = $shiftCache[$employee->id.'|'.$date] ??= $employee->shiftForDate($cursor);

                if (! $shift) {
                    $cursor->addDay();

                    continue;
                }

                $roll = random_int(1, 100);

                if ($holidays->has($date) && $roll <= 40) {
                    $this->row($employee, $shift, $date, Attendance::STATUS_HOLIDAY);

                    $cursor->addDay();

                    continue;
                }

                if ($roll <= 4) {
                    $this->row($employee, $shift, $date, Attendance::STATUS_ABSENT, source: 'auto');

                    $cursor->addDay();

                    continue;
                }

                if ($roll <= 7 && $leaveTypes->isNotEmpty()) {
                    $type = $leaveTypes[random_int(0, $leaveTypes->count() - 1)];
                    LeaveRequest::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'date_from' => $date,
                        'date_to' => $date,
                        'days' => 1,
                        'is_paid' => true,
                        'reason' => 'Approved '.$type->code.' (seeded)',
                        'status' => LeaveRequest::STATUS_APPROVED,
                        'approved_at' => $cursor->copy()->subDay(),
                    ]);
                    $this->row($employee, $shift, $date, Attendance::STATUS_LEAVE);

                    $cursor->addDay();

                    continue;
                }

                $in = $cursor->copy()->setTimeFromTimeString($shift->time_in);
                if (random_int(1, 100) <= 20) {
                    $in->addMinutes(random_int(6, 30));
                }
                $in->addSeconds(random_int(0, 59));

                $out = $cursor->copy()->setTimeFromTimeString($shift->time_out);
                if ($shift->time_out <= $shift->time_in) {
                    $out->addDay();
                }

                $forgotOut = random_int(1, 100) <= 3;
                if (! $forgotOut) {
                    $swing = random_int(1, 100);
                    if ($swing <= 25) {
                        $out->addMinutes(random_int(30, 120));
                    } elseif ($swing <= 35) {
                        $out->subMinutes(random_int(15, 60));
                    }
                    $out->addSeconds(random_int(0, 59));
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'branch_id' => $employee->branch_id,
                    'shift_id' => $shift->id,
                    'work_date' => $date,
                    'time_in' => $in,
                    'time_out' => $forgotOut ? null : $out,
                    'status' => Attendance::STATUS_PRESENT,
                    'source' => random_int(1, 100) <= 70 ? 'biometric' : 'kiosk',
                ]);

                $dtr->process($attendance);

                $cursor->addDay();
            }
        }

        $this->command?->info('AttendanceSeeder: '.Attendance::count().' rows.');
    }

    private function row(Employee $employee, $shift, string $date, string $status, string $source = 'biometric'): void
    {
        Attendance::create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'shift_id' => $shift->id,
            'work_date' => $date,
            'status' => $status,
            'source' => $source,
        ]);
    }
}
