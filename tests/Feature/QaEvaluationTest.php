<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\QaCriterion;
use App\Models\QaEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private Branch $ormoc;

    private User $hr;

    private QaCriterion $knowledge;

    private QaCriterion $softSkills;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->ormoc = Branch::create(['name' => 'Ormoc Site', 'code' => 'ORM']);
        $this->hr = $this->user(User::ROLE_HR, 'hr@test.local', $this->hq);
        $this->knowledge = QaCriterion::create(['name' => 'Product knowledge', 'weight' => 60]);
        $this->softSkills = QaCriterion::create(['name' => 'Soft skills', 'weight' => 40]);
    }

    private function user(string $role, string $email, ?Branch $branch = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $branch?->id,
        ]);
    }

    private function employee(Branch $branch, ?User $user = null, string $lastName = 'Cruz'): Employee
    {
        return Employee::create([
            'employee_no' => 'E-'.random_int(100, 999),
            'first_name' => 'Juan', 'last_name' => $lastName,
            'branch_id' => $branch->id, 'user_id' => $user?->id, 'is_active' => true,
        ]);
    }

    private function evaluation(Employee $employee, float $score): QaEvaluation
    {
        return QaEvaluation::create([
            'employee_id' => $employee->id, 'branch_id' => $employee->branch_id,
            'evaluated_on' => now()->toDateString(), 'score' => $score,
        ]);
    }

    public function test_weighted_total_is_computed(): void
    {
        $score = QaEvaluation::computeScore([
            $this->knowledge->id => 90,
            $this->softSkills->id => 80,
        ]);

        $this->assertSame(86.0, $score);
    }

    public function test_hr_records_a_scorecard_and_notifies_the_employee(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local', $this->hq);
        $employee = $this->employee($this->hq, $user);

        $this->actingAs($this->hr)->post(route('qa.store'), [
            'employee_id' => $employee->id,
            'evaluated_on' => now()->toDateString(),
            'reference' => 'CALL-001',
            'remarks' => 'Great discovery questions.',
            'coaching_notes' => 'Shorten the hold time.',
            'scores' => [$this->knowledge->id => 90, $this->softSkills->id => 80],
        ])->assertRedirect();

        $evaluation = QaEvaluation::firstOrFail();

        $this->assertSame('86.00', $evaluation->score);
        $this->assertSame($this->hq->id, $evaluation->branch_id);
        $this->assertSame($this->hr->id, $evaluation->evaluator_id);
        $this->assertDatabaseCount('qa_evaluation_scores', 2);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('Passing', $evaluation->statusLabel());
    }

    public function test_a_scorecard_needs_at_least_one_score(): void
    {
        $employee = $this->employee($this->hq);

        $this->actingAs($this->hr)->post(route('qa.store'), [
            'employee_id' => $employee->id,
            'evaluated_on' => now()->toDateString(),
            'scores' => [$this->knowledge->id => '', $this->softSkills->id => ''],
        ])->assertSessionHasErrors('scores');

        $this->assertDatabaseCount('qa_evaluations', 0);
    }

    public function test_manager_only_sees_their_own_branch(): void
    {
        $manager = $this->user(User::ROLE_MANAGER, 'mgr@test.local', $this->hq);
        $hqEmployee = $this->employee($this->hq);
        $ormocEmployee = $this->employee($this->ormoc, null, 'Santos');

        $visible = $this->evaluation($hqEmployee, 90);
        $hidden = $this->evaluation($ormocEmployee, 70);

        $this->actingAs($manager)->get(route('qa.index'))
            ->assertOk()
            ->assertSee('Cruz')
            ->assertDontSee('Santos');

        $this->actingAs($manager)->get(route('qa.show', $hidden))->assertForbidden();
        $this->actingAs($manager)->get(route('qa.show', $visible))->assertOk();
    }

    public function test_a_below_passing_score_notifies_hr(): void
    {
        $employee = $this->employee($this->hq);

        $this->actingAs($this->hr)->post(route('qa.store'), [
            'employee_id' => $employee->id,
            'evaluated_on' => now()->toDateString(),
            'scores' => [$this->knowledge->id => 50, $this->softSkills->id => 60],
        ])->assertRedirect();

        $evaluation = QaEvaluation::firstOrFail();

        $this->assertSame('54.00', $evaluation->score);
        $this->assertFalse($evaluation->isPassing());
        $this->assertSame('Needs coaching', $evaluation->statusLabel());
        $this->assertSame(1, $this->hr->notifications()->count());
    }

    public function test_employee_sees_their_own_scorecards(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local', $this->hq);
        $employee = $this->employee($this->hq, $user);
        $this->evaluation($employee, 92);

        $this->actingAs($user)->get(route('me.scorecards'))
            ->assertOk()
            ->assertSee('92.00');
    }

    public function test_criteria_can_be_managed(): void
    {
        $this->actingAs($this->hr)->post(route('qa-criteria.store'), [
            'name' => 'Attendance and punctuality', 'weight' => 10, 'sort' => 5, 'is_active' => 1,
        ])->assertRedirect();

        $criterion = QaCriterion::where('name', 'Attendance and punctuality')->firstOrFail();

        $this->actingAs($this->hr)->put(route('qa-criteria.update', $criterion), [
            'name' => 'Attendance', 'weight' => 5, 'sort' => 6, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame('Attendance', $criterion->fresh()->name);
    }

    public function test_criteria_in_use_cannot_be_deleted(): void
    {
        $employee = $this->employee($this->hq);

        $this->actingAs($this->hr)->post(route('qa.store'), [
            'employee_id' => $employee->id,
            'evaluated_on' => now()->toDateString(),
            'scores' => [$this->knowledge->id => 90],
        ])->assertRedirect();

        $this->actingAs($this->hr)->delete(route('qa-criteria.destroy', $this->knowledge))->assertSessionHas('error');

        $this->assertDatabaseHas('qa_criteria', ['id' => $this->knowledge->id]);
    }

    public function test_passing_score_setting_is_respected(): void
    {
        $this->actingAs($this->hr)->put(route('qa.settings'), ['passing_score' => 90])->assertRedirect();

        $this->assertSame(90.0, QaEvaluation::passingScore());

        $employee = $this->employee($this->hq);
        $evaluation = $this->evaluation($employee, 86);

        $this->assertFalse($evaluation->isPassing());
        $this->assertSame('Needs coaching', $evaluation->statusLabel());
    }

    public function test_employee_cannot_open_the_qa_module(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local', $this->hq);

        $this->actingAs($user)->get(route('qa.index'))->assertForbidden();
    }
}
