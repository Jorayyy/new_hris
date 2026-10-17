<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\QaCriterion;
use App\Models\QaEvaluation;
use App\Support\Notify;
use App\Support\TeamScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QaEvaluationController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $from, $to] = $this->monthRange($request);

        $evaluations = QaEvaluation::query()
            ->with(['employee', 'evaluator'])
            ->tap(fn ($query) => TeamScope::apply($query, $request->user(), 'branch_id'))
            ->whereBetween('evaluated_on', [$from, $to])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->whereHas('employee', fn ($e) => $e->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('employee_no', 'like', $term));
            })
            ->when($request->boolean('below'), fn ($query) => $query->whereNotNull('score')->where('score', '<', QaEvaluation::passingScore()))
            ->orderByDesc('evaluated_on')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summary = Employee::query()
            ->with('branch')
            ->tap(fn ($query) => TeamScope::apply($query, $request->user(), 'branch_id'))
            ->withCount(['qaEvaluations as evaluation_count' => fn ($q) => $q->whereBetween('evaluated_on', [$from, $to])])
            ->withAvg(['qaEvaluations as average_score' => fn ($q) => $q->whereBetween('evaluated_on', [$from, $to])], 'score')
            ->get()
            ->filter(fn (Employee $employee) => $employee->evaluation_count > 0)
            ->sortBy(fn (Employee $employee) => (float) $employee->average_score)
            ->take(20)
            ->values();

        return view('qa.index', [
            'evaluations' => $evaluations,
            'summary' => $summary,
            'month' => $month,
            'below' => $request->boolean('below'),
            'passing' => QaEvaluation::passingScore(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('qa.form', [
            'evaluation' => new QaEvaluation(['evaluated_on' => now()->toDateString()]),
            'criteria' => QaCriterion::query()->active()->get(),
            'employees' => TeamScope::employees($request->user()),
            'passing' => QaEvaluation::passingScore(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluated_on' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'coaching_notes' => ['nullable', 'string', 'max:2000'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        TeamScope::authorizeEmployee($request->user(), $employee);

        $scores = $this->filledScores($data['scores']);

        $evaluation = DB::transaction(function () use ($request, $data, $employee, $scores) {
            $evaluation = QaEvaluation::create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'evaluator_id' => $request->user()->id,
                'evaluated_on' => $data['evaluated_on'],
                'reference' => $data['reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'coaching_notes' => $data['coaching_notes'] ?? null,
                'score' => QaEvaluation::computeScore($scores),
            ]);

            $this->saveScores($evaluation, $scores);

            return $evaluation;
        });

        AuditLog::record('create', 'Recorded QA scorecard for '.$employee->full_name.' ('.$evaluation->score.')', $evaluation);
        Notify::employee($employee, 'QA scorecard recorded', 'Score: '.(float) $evaluation->score.'% on '.$evaluation->evaluated_on->format('M d, Y').'.', route('me.scorecards'), '◎');

        if (! $evaluation->isPassing()) {
            Notify::approvers('QA score below passing', $employee->full_name.' scored '.(float) $evaluation->score.'%.', route('qa.show', $evaluation), '◎', $employee);
        }

        return redirect()->route('qa.show', $evaluation)->with('ok', 'Scorecard saved.');
    }

    public function show(Request $request, QaEvaluation $qaEvaluation): View
    {
        TeamScope::authorize($request->user(), $qaEvaluation->branch_id);

        $qaEvaluation->load(['employee.branch', 'evaluator', 'scores.criterion']);

        return view('qa.show', [
            'evaluation' => $qaEvaluation,
            'passing' => QaEvaluation::passingScore(),
        ]);
    }

    public function edit(Request $request, QaEvaluation $qaEvaluation): View
    {
        TeamScope::authorize($request->user(), $qaEvaluation->branch_id);

        $qaEvaluation->load('scores');

        return view('qa.form', [
            'evaluation' => $qaEvaluation,
            'criteria' => QaCriterion::query()->active()->get(),
            'employees' => TeamScope::employees($request->user()),
            'passing' => QaEvaluation::passingScore(),
        ]);
    }

    public function update(Request $request, QaEvaluation $qaEvaluation): RedirectResponse
    {
        TeamScope::authorize($request->user(), $qaEvaluation->branch_id);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluated_on' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'coaching_notes' => ['nullable', 'string', 'max:2000'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        TeamScope::authorizeEmployee($request->user(), $employee);

        $scores = $this->filledScores($data['scores']);

        DB::transaction(function () use ($qaEvaluation, $data, $employee, $scores) {
            $qaEvaluation->update([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'evaluated_on' => $data['evaluated_on'],
                'reference' => $data['reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'coaching_notes' => $data['coaching_notes'] ?? null,
                'score' => QaEvaluation::computeScore($scores),
            ]);

            $qaEvaluation->scores()->delete();
            $this->saveScores($qaEvaluation, $scores);
        });

        AuditLog::record('update', 'Updated QA scorecard #'.$qaEvaluation->id.' for '.$employee->full_name, $qaEvaluation);

        return redirect()->route('qa.show', $qaEvaluation)->with('ok', 'Scorecard updated.');
    }

    public function destroy(Request $request, QaEvaluation $qaEvaluation): RedirectResponse
    {
        TeamScope::authorize($request->user(), $qaEvaluation->branch_id);

        AuditLog::record('delete', 'Deleted QA scorecard #'.$qaEvaluation->id, $qaEvaluation);

        $qaEvaluation->delete();

        return redirect()->route('qa.index')->with('ok', 'Scorecard deleted.');
    }

    private function monthRange(Request $request): array
    {
        $month = $request->string('month')->toString();
        $start = preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth()
            : now()->startOfMonth();

        return [$start->format('Y-m'), $start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    /**
     * @param  array<int|string, mixed>  $scores
     * @return array<int|string, mixed>
     */
    private function filledScores(array $scores): array
    {
        $scores = array_filter($scores, fn ($value) => $value !== null && $value !== '');

        if ($scores === []) {
            throw ValidationException::withMessages(['scores' => 'Enter at least one criterion score.']);
        }

        return $scores;
    }

    /**
     * @param  array<int|string, mixed>  $scores
     */
    private function saveScores(QaEvaluation $evaluation, array $scores): void
    {
        foreach ($scores as $criterionId => $score) {
            $evaluation->scores()->create(['qa_criterion_id' => $criterionId, 'score' => $score]);
        }
    }
}
