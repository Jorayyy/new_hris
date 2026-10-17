<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Support\Notify;
use Illuminate\Console\Command;

class RegularizationAlertCommand extends Command
{
    protected $signature = 'employees:regularization-alerts';

    protected $description = 'Notify HR about probationary employees due for regularization.';

    public function handle(): int
    {
        $due = Employee::query()
            ->where('is_active', true)
            ->where('employment_status', 'probationary')
            ->whereNotNull('date_hired')
            ->get()
            ->filter(fn (Employee $employee) => ($days = $employee->regularizationDaysLeft()) !== null
                && $days <= Employee::REGULARIZATION_WARN_DAYS)
            ->sortBy(fn (Employee $employee) => $employee->regularizationDaysLeft())
            ->values();

        if ($due->isEmpty()) {
            $this->info('No employees due for regularization.');

            return self::SUCCESS;
        }

        $overdue = $due->filter(fn (Employee $employee) => $employee->regularizationDaysLeft() < 0)->count();

        $body = $due->count().' probationary employee(s) for regularization, '.$overdue.' overdue.';

        $sample = $due->take(10)->map(function (Employee $employee) {
            $days = $employee->regularizationDaysLeft();
            $when = $days < 0 ? abs($days).' day(s) overdue' : "due in {$days} day(s)";

            return $employee->full_name.' ('.$employee->employee_no.') — '.$when.', '.$employee->regularizationDueOn()->format('M d, Y');
        })->implode("\n");

        Notify::hr(
            'Probationary employees due for regularization',
            $body."\n".$sample,
            route('employees.index', ['status' => 'probationary']),
            '★',
        );

        $this->info('Notified HR about '.$due->count().' employee(s) for regularization.');

        return self::SUCCESS;
    }
}
