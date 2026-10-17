<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCaseAction;
use App\Models\Employee;
use App\Support\Notify;
use App\Support\TeamScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DisciplinaryCaseController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'all';

        $cases = DisciplinaryCase::query()
            ->with(['employee', 'filer'])
            ->tap(fn ($query) => TeamScope::apply($query, $request->user(), 'branch_id'))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->whereHas('employee', fn ($e) => $e->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('employee_no', 'like', $term));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cases.index', [
            'cases' => $cases,
            'status' => $status,
            'statuses' => DisciplinaryCase::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('cases.form', [
            'case' => new DisciplinaryCase([
                'type' => DisciplinaryCase::TYPE_INCIDENT,
                'severity' => 'medium',
                'incident_date' => now()->toDateString(),
            ]),
            'employees' => TeamScope::employees($request->user()),
            'types' => DisciplinaryCase::TYPES,
            'severities' => DisciplinaryCase::SEVERITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(array_keys(DisciplinaryCase::TYPES))],
            'severity' => ['required', Rule::in(array_keys(DisciplinaryCase::SEVERITIES))],
            'incident_date' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:5000'],
            'response_due_on' => ['nullable', 'date'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        TeamScope::authorizeEmployee($request->user(), $employee);

        $case = DisciplinaryCase::create($data + [
            'branch_id' => $employee->branch_id,
            'filed_by' => $request->user()->id,
            'status' => $data['type'] === DisciplinaryCase::TYPE_NTE ? DisciplinaryCase::STATUS_AWAITING : DisciplinaryCase::STATUS_OPEN,
        ]);

        $case->actions()->create([
            'user_id' => $request->user()->id,
            'type' => DisciplinaryCaseAction::TYPE_FILED,
            'body' => $case->details ?: $case->subject,
        ]);

        AuditLog::record('create', 'Filed '.$case->type.' case for '.$employee->full_name, $case);
        Notify::approvers('Case filed', $employee->full_name.' — '.$case->subject, route('cases.show', $case), '⚑', $employee);

        if ($case->isOpenForResponse()) {
            Notify::employee($employee, 'Notice to explain', $case->subject, route('me.cases.show', $case), '⚑');
        }

        return redirect()->route('cases.show', $case)->with('ok', 'Case filed.');
    }

    public function show(Request $request, DisciplinaryCase $case): View
    {
        TeamScope::authorize($request->user(), $case->branch_id);

        $case->load(['employee', 'branch', 'filer', 'decider', 'actions.user']);

        return view('cases.show', [
            'case' => $case,
            'statuses' => DisciplinaryCase::STATUSES,
        ]);
    }

    public function storeAction(Request $request, DisciplinaryCase $case): RedirectResponse
    {
        TeamScope::authorize($request->user(), $case->branch_id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $case->actions()->create([
            'user_id' => $request->user()->id,
            'type' => DisciplinaryCaseAction::TYPE_NOTE,
            'body' => $data['body'],
            'file_path' => $request->hasFile('file') ? $request->file('file')->store('cases', 'public') : null,
        ]);

        AuditLog::record('update', 'Added a note to case #'.$case->id, $case);

        return back()->with('ok', 'Note added.');
    }

    public function decide(Request $request, DisciplinaryCase $case): RedirectResponse
    {
        TeamScope::authorize($request->user(), $case->branch_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DisciplinaryCase::STATUSES))],
            'decision' => ['nullable', 'string', 'max:2000'],
            'sanction' => ['nullable', 'string', 'max:200'],
        ]);

        $case->update($data + [
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $case->actions()->create([
            'user_id' => $request->user()->id,
            'type' => DisciplinaryCaseAction::TYPE_DECISION,
            'body' => trim(($data['decision'] ?? 'Status set to '.$case->status_label).($data['sanction'] ? ' — Sanction: '.$data['sanction'] : '')),
        ]);

        AuditLog::record('update', 'Case #'.$case->id.' set to '.$case->status, $case);
        Notify::employee($case->employee, 'Case '.strtolower($case->status_label), 'Case: '.$case->subject, route('me.cases.show', $case), '⚑');

        return back()->with('ok', 'Case updated.');
    }
}
