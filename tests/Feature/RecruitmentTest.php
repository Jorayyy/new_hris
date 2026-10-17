<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ApplicantActivity;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private Branch $ormoc;

    private User $hr;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->ormoc = Branch::create(['name' => 'Ormoc Site', 'code' => 'ORM']);
        $this->hr = $this->user(User::ROLE_HR, 'hr@test.local', $this->hq);
        $this->manager = $this->user(User::ROLE_MANAGER, 'mgr@test.local', $this->hq);
    }

    private function user(string $role, string $email, ?Branch $branch = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $branch?->id,
        ]);
    }

    private function vacancy(array $attributes = []): JobVacancy
    {
        return JobVacancy::create(array_merge([
            'title' => 'Customer Service Rep',
            'branch_id' => $this->hq->id,
            'employment_type' => 'full_time',
            'openings' => 2,
            'status' => JobVacancy::STATUS_OPEN,
            'posted_on' => now()->toDateString(),
        ], $attributes));
    }

    private function applicant(JobVacancy $vacancy, array $attributes = []): Applicant
    {
        return $vacancy->applicants()->create(array_merge([
            'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'ana@test.local',
            'stage' => Applicant::STAGE_APPLIED, 'applied_on' => now()->toDateString(),
        ], $attributes));
    }

    private function employee(): Employee
    {
        return Employee::create([
            'employee_no' => 'E-'.random_int(100, 999),
            'first_name' => 'Existing', 'last_name' => 'Staff',
            'branch_id' => $this->hq->id, 'is_active' => true,
        ]);
    }

    public function test_hr_can_create_a_vacancy_and_is_notified(): void
    {
        $this->actingAs($this->hr)->post('/vacancies', [
            'title' => 'Team Lead', 'branch_id' => $this->hq->id, 'employment_type' => 'full_time',
            'openings' => 3, 'status' => JobVacancy::STATUS_OPEN, 'posted_on' => now()->toDateString(),
        ])->assertRedirect();

        $vacancy = JobVacancy::firstOrFail();
        $this->assertSame('Team Lead', $vacancy->title);
        $this->assertSame($this->hr->id, $vacancy->created_by);
        $this->assertSame(1, AuditLog::where('action', 'create')->count());
        $this->assertSame(1, $this->hr->notifications()->count());
    }

    public function test_manager_can_browse_but_not_change_recruitment(): void
    {
        $vacancy = $this->vacancy();
        $applicant = $this->applicant($vacancy);

        $this->actingAs($this->manager)->get('/vacancies')->assertOk();
        $this->actingAs($this->manager)->get('/vacancies/'.$vacancy->id)->assertOk();
        $this->actingAs($this->manager)->get('/applicants/'.$applicant->id)->assertOk();
        $this->actingAs($this->manager)->get('/vacancies/create')->assertForbidden();
        $this->actingAs($this->manager)->get('/vacancies/'.$vacancy->id.'/edit')->assertForbidden();
        $this->actingAs($this->manager)->post('/vacancies', [
            'title' => 'X', 'employment_type' => 'full_time', 'openings' => 1, 'status' => JobVacancy::STATUS_OPEN,
        ])->assertForbidden();
        $this->actingAs($this->manager)->post('/vacancies/'.$vacancy->id.'/applicants', [
            'first_name' => 'A', 'last_name' => 'B',
        ])->assertForbidden();
        $this->actingAs($this->manager)->put('/applicants/'.$applicant->id, [
            'stage' => Applicant::STAGE_HIRED,
        ])->assertForbidden();
    }

    public function test_hr_can_open_vacancy_and_applicant_admin_pages(): void
    {
        $applicant = $this->applicant($this->vacancy());

        $this->actingAs($this->hr)->get('/vacancies/'.$applicant->job_vacancy_id.'/edit')->assertOk();
        $this->actingAs($this->hr)->get('/applicants/'.$applicant->id)->assertOk()->assertSee($applicant->full_name);
    }

    public function test_manager_only_sees_vacancies_in_their_branch(): void
    {
        $other = $this->vacancy(['branch_id' => $this->ormoc->id, 'title' => 'Ormoc Only']);

        $this->actingAs($this->manager)->get('/vacancies')->assertOk()->assertDontSee('Ormoc Only');
        $this->actingAs($this->manager)->get('/vacancies/'.$other->id)->assertForbidden();
    }

    public function test_applicant_defaults_and_history_entry(): void
    {
        $vacancy = $this->vacancy();

        $this->actingAs($this->hr)->post('/vacancies/'.$vacancy->id.'/applicants', [
            'first_name' => 'Ana', 'last_name' => 'Reyes', 'source' => 'Referral',
        ])->assertRedirect();

        $applicant = $vacancy->applicants()->firstOrFail();
        $this->assertSame(Applicant::STAGE_APPLIED, $applicant->stage);
        $this->assertNotNull($applicant->applied_on);
        $this->assertSame(1, $applicant->activities()->count());
    }

    public function test_stage_change_is_logged_in_history(): void
    {
        $applicant = $this->applicant($this->vacancy());

        $this->actingAs($this->hr)->put('/applicants/'.$applicant->id, [
            'stage' => Applicant::STAGE_INTERVIEW, 'note' => 'Panel on Thursday',
        ])->assertRedirect();

        $applicant->refresh();
        $this->assertSame(Applicant::STAGE_INTERVIEW, $applicant->stage);

        $activity = $applicant->activities()->first();
        $this->assertSame(ApplicantActivity::TYPE_STAGE, $activity->type);
        $this->assertSame(Applicant::STAGE_INTERVIEW, $activity->stage);
        $this->assertStringContainsString('Panel on Thursday', $activity->notes);
        $this->assertSame($this->hr->id, $activity->user_id);
        $this->assertSame(1, AuditLog::where('action', 'update')->count());
    }

    public function test_note_alone_does_not_change_stage(): void
    {
        $applicant = $this->applicant($this->vacancy());

        $this->actingAs($this->hr)->put('/applicants/'.$applicant->id, [
            'stage' => Applicant::STAGE_APPLIED, 'note' => 'Called, no answer',
        ])->assertRedirect();

        $applicant->refresh();
        $this->assertSame(Applicant::STAGE_APPLIED, $applicant->stage);
        $this->assertSame(ApplicantActivity::TYPE_NOTE, $applicant->activities()->first()->type);
    }

    public function test_hire_creates_employee_from_vacancy_defaults(): void
    {
        $vacancy = $this->vacancy(['openings' => 1]);
        $applicant = $this->applicant($vacancy, ['contact_no' => '0917']);

        $this->actingAs($this->hr)->post('/applicants/'.$applicant->id.'/hire', [
            'employee_no' => 'E-1001', 'basic_salary' => 25000, 'date_hired' => '2026-02-01',
            'probation_months' => 6, 'tax_status' => 'S',
        ])->assertRedirect();

        $applicant->refresh();
        $employee = Employee::firstOrFail();

        $this->assertSame('E-1001', $employee->employee_no);
        $this->assertSame($vacancy->branch_id, $employee->branch_id);
        $this->assertSame('probationary', $employee->employment_status);
        $this->assertSame('2026-02-01', $employee->date_hired->toDateString());
        $this->assertTrue($employee->is_active);
        $this->assertSame($employee->id, $applicant->employee_id);
        $this->assertSame(Applicant::STAGE_HIRED, $applicant->stage);
    }

    public function test_applicant_cannot_be_hired_twice(): void
    {
        $applicant = $this->applicant($this->vacancy(), ['employee_id' => $this->employee()->id]);

        $this->actingAs($this->hr)->post('/applicants/'.$applicant->id.'/hire', [
            'employee_no' => 'E-2002', 'basic_salary' => 20000, 'tax_status' => 'S',
        ])->assertRedirect();

        $this->assertSame(1, Employee::count());
    }

    public function test_vacancy_with_applicants_cannot_be_deleted(): void
    {
        $vacancy = $this->vacancy();
        $this->applicant($vacancy);

        $this->actingAs($this->hr)->delete('/vacancies/'.$vacancy->id)->assertRedirect();

        $this->assertSame(1, JobVacancy::count());
    }

    public function test_empty_vacancy_can_be_deleted(): void
    {
        $vacancy = $this->vacancy();

        $this->actingAs($this->hr)->delete('/vacancies/'.$vacancy->id)
            ->assertRedirect(route('vacancies.index'));

        $this->assertSame(0, JobVacancy::count());
    }

    public function test_applicant_requires_a_name(): void
    {
        $vacancy = $this->vacancy();

        $this->actingAs($this->hr)->post('/vacancies/'.$vacancy->id.'/applicants', ['first_name' => ''])
            ->assertSessionHasErrors('first_name');
    }
}
