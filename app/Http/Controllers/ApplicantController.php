<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantActivity;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Support\TeamScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    public function show(Request $request, Applicant $applicant): View
    {
        $applicant->load(['vacancy.branch', 'vacancy.position', 'employee', 'activities.user']);
        TeamScope::authorize($request->user(), $applicant->vacancy?->branch_id);

        return view('recruitment.applicant', [
            'applicant' => $applicant,
            'vacancy' => $applicant->vacancy,
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $applicant = $vacancy->applicants()->create($this->validated($request) + [
            'stage' => Applicant::STAGE_APPLIED,
            'applied_on' => $request->input('applied_on') ?: now()->toDateString(),
        ]);
        $applicant->logActivity(ApplicantActivity::TYPE_NOTE, 'Applicant added'.($applicant->source ? ' via '.$applicant->source : ''));

        AuditLog::record('create', 'Added applicant '.$applicant->full_name.' for '.$vacancy->title, $applicant);

        return back()->with('ok', 'Applicant added.');
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(array_keys(Applicant::STAGES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $stageChanged = $applicant->stage !== $data['stage'];
        $note = trim((string) ($data['note'] ?? ''));

        if (! $stageChanged && $note === '') {
            return back()->with('error', 'Nothing to update.');
        }

        if ($stageChanged) {
            $from = $applicant->stageLabel();
            $applicant->update(['stage' => $data['stage']]);
            $applicant->logActivity(ApplicantActivity::TYPE_STAGE, $from.' → '.$applicant->stageLabel().($note !== '' ? ' — '.$note : ''));
            AuditLog::record('update', 'Applicant '.$applicant->full_name.' moved to '.$applicant->stageLabel(), $applicant);
        } else {
            $applicant->logActivity(ApplicantActivity::TYPE_NOTE, $note);
        }

        return back()->with('ok', $applicant->full_name.' updated.');
    }

    public function hire(Request $request, Applicant $applicant): RedirectResponse
    {
        if ($applicant->employee_id) {
            return back()->with('error', 'This applicant is already linked to an employee record.');
        }

        $vacancy = $applicant->vacancy;
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:50', 'unique:employees,employee_no'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'date_hired' => ['nullable', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'probation_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'tax_status' => ['required', 'string', 'max:10'],
        ]);

        $employee = DB::transaction(function () use ($applicant, $vacancy, $data) {
            $employee = Employee::create([
                'employee_no' => $data['employee_no'],
                'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name,
                'personal_email' => $applicant->email,
                'contact_no' => $applicant->contact_no,
                'branch_id' => $data['branch_id'] ?? $vacancy->branch_id,
                'department_id' => $data['department_id'] ?? $vacancy->department_id,
                'position_id' => $data['position_id'] ?? $vacancy->position_id,
                'date_hired' => $data['date_hired'] ?? now()->toDateString(),
                'employment_status' => 'probationary',
                'probation_months' => $data['probation_months'] ?: 6,
                'basic_salary' => $data['basic_salary'],
                'tax_status' => $data['tax_status'],
                'is_active' => true,
            ]);

            $applicant->update(['stage' => Applicant::STAGE_HIRED, 'employee_id' => $employee->id]);
            $applicant->logActivity(ApplicantActivity::TYPE_STAGE, 'Hired as '.$employee->employee_no);

            return $employee;
        });

        AuditLog::record('create', 'Hired applicant '.$applicant->full_name.' as '.$employee->employee_no, $employee);

        return redirect()->route('employees.show', $employee)->with('ok', $applicant->full_name.' hired. Complete the employee record.');
    }

    public function destroy(Applicant $applicant): RedirectResponse
    {
        if ($applicant->employee_id) {
            return back()->with('error', 'Hired applicants cannot be deleted.');
        }

        $name = $applicant->full_name;
        $vacancyId = $applicant->job_vacancy_id;

        AuditLog::record('delete', 'Deleted applicant '.$name, $applicant);
        $applicant->delete();

        return redirect()->route('vacancies.show', $vacancyId)->with('ok', $name.' removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:50'],
            'applied_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
