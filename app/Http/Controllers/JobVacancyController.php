<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Support\Notify;
use App\Support\TeamScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobVacancyController extends Controller
{
    public function index(Request $request): View
    {
        $vacancies = TeamScope::apply(JobVacancy::query()->with(['branch', 'department', 'position']), $request->user())
            ->withCount(['applicants', 'applicants as hired_count' => fn ($q) => $q->where('stage', Applicant::STAGE_HIRED)])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('recruitment.index', compact('vacancies'));
    }

    public function create(): View
    {
        return view('recruitment.form', $this->formData(new JobVacancy([
            'employment_type' => 'full_time',
            'status' => JobVacancy::STATUS_OPEN,
            'openings' => 1,
            'posted_on' => now()->toDateString(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $vacancy = JobVacancy::create($data + ['created_by' => $request->user()->id]);

        AuditLog::record('create', 'Opened vacancy '.$vacancy->title, $vacancy);
        Notify::hr('Vacancy opened: '.$vacancy->title, 'Applicants can now be logged against this vacancy.', route('vacancies.show', $vacancy), '☰');

        return redirect()->route('vacancies.show', $vacancy)->with('ok', 'Vacancy created.');
    }

    public function show(Request $request, JobVacancy $vacancy): View
    {
        TeamScope::authorize($request->user(), $vacancy->branch_id);
        $vacancy->load(['branch', 'department', 'position', 'applicants' => fn ($q) => $q->with('employee')->orderBy('last_name')]);

        return view('recruitment.show', compact('vacancy'));
    }

    public function edit(Request $request, JobVacancy $vacancy): View
    {
        TeamScope::authorize($request->user(), $vacancy->branch_id);

        return view('recruitment.form', $this->formData($vacancy));
    }

    public function update(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $vacancy->update($this->validated($request));

        AuditLog::record('update', 'Updated vacancy '.$vacancy->title, $vacancy);

        return redirect()->route('vacancies.show', $vacancy)->with('ok', 'Vacancy updated.');
    }

    public function destroy(JobVacancy $vacancy): RedirectResponse
    {
        if ($vacancy->applicants()->exists()) {
            return back()->with('error', 'Vacancies with applicants cannot be deleted. Close the vacancy instead.');
        }

        AuditLog::record('delete', 'Deleted vacancy '.$vacancy->title, $vacancy);
        $vacancy->delete();

        return redirect()->route('vacancies.index')->with('ok', 'Vacancy deleted.');
    }

    private function formData(JobVacancy $vacancy): array
    {
        return [
            'vacancy' => $vacancy,
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'employment_type' => ['required', Rule::in(array_keys(JobVacancy::EMPLOYMENT_TYPES))],
            'openings' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', Rule::in(array_keys(JobVacancy::STATUSES))],
            'posted_on' => ['nullable', 'date'],
            'closes_on' => ['nullable', 'date', 'after_or_equal:posted_on'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
