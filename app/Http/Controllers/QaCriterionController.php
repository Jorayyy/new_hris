<?php

namespace App\Http\Controllers;

use App\Models\QaCriterion;
use App\Models\QaEvaluation;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QaCriterionController extends Controller
{
    public function index(): View
    {
        return view('qa.criteria', [
            'criteria' => QaCriterion::withCount('scores')->orderBy('sort')->orderBy('name')->paginate(30),
            'passing' => QaEvaluation::passingScore(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        QaCriterion::create($this->validated($request));

        return back()->with('ok', 'Criteria created.');
    }

    public function update(Request $request, QaCriterion $qaCriterion): RedirectResponse
    {
        $qaCriterion->update($this->validated($request));

        return back()->with('ok', 'Criteria updated.');
    }

    public function destroy(QaCriterion $qaCriterion): RedirectResponse
    {
        if ($qaCriterion->scores()->exists()) {
            return back()->with('error', 'Cannot delete criteria that have been scored. Deactivate it instead.');
        }

        $qaCriterion->delete();

        return back()->with('ok', 'Criteria deleted.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'passing_score' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        Setting::put('qa_passing_score', $data['passing_score']);

        return back()->with('ok', 'Passing score saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'sort' => $request->integer('sort'),
        ];
    }
}
