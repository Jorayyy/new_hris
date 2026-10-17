<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClearanceItem;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->hr = $this->user(User::ROLE_HR, 'hr@test.local', $this->hq);
    }

    private function user(string $role, string $email, ?Branch $branch = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $branch?->id,
        ]);
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create($attributes + [
            'employee_no' => 'E-'.random_int(100, 999),
            'first_name' => 'Juan', 'last_name' => 'Cruz',
            'branch_id' => $this->hq->id, 'is_active' => true,
        ]);
    }

    public function test_regularization_due_date_uses_probation_months(): void
    {
        $employee = $this->employee([
            'date_hired' => '2026-01-01', 'probation_months' => 3, 'employment_status' => 'probationary',
        ]);

        $this->assertSame('2026-04-01', $employee->regularizationDueOn()->toDateString());
    }

    public function test_hr_can_mark_a_probationary_employee_regular(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local', $this->hq);
        $employee = $this->employee([
            'date_hired' => now()->subMonths(7)->toDateString(),
            'employment_status' => 'probationary', 'user_id' => $user->id,
        ]);

        $this->actingAs($this->hr)->post(route('employees.regularize', $employee))->assertRedirect();

        $employee->refresh();
        $this->assertSame('regular', $employee->employment_status);
        $this->assertSame(now()->toDateString(), $employee->date_regularized->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'regularize', 'subject_id' => $employee->id]);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_a_separated_employee_cannot_be_regularized(): void
    {
        $employee = $this->employee(['employment_status' => 'separated', 'is_active' => false]);

        $this->actingAs($this->hr)->post(route('employees.regularize', $employee))->assertSessionHas('error');

        $this->assertSame('separated', $employee->fresh()->employment_status);
    }

    public function test_separating_an_employee_disables_login_and_starts_clearance(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local', $this->hq);
        $employee = $this->employee(['employment_status' => 'regular', 'user_id' => $user->id]);

        $this->actingAs($this->hr)->post(route('employees.separate', $employee), [
            'date_separated' => now()->toDateString(),
            'separation_reason' => 'Resigned',
        ])->assertRedirect();

        $employee->refresh();
        $this->assertFalse($employee->is_active);
        $this->assertSame('separated', $employee->employment_status);
        $this->assertSame('Resigned', $employee->separation_reason);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(count(Employee::CLEARANCE_ITEMS), $employee->clearanceItems()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'separate', 'subject_id' => $employee->id]);
    }

    public function test_clearance_items_can_be_ticked_off(): void
    {
        $employee = $this->employee(['employment_status' => 'separated', 'is_active' => false]);
        $employee->startClearance();
        $item = $employee->clearanceItems()->firstOrFail();

        $this->actingAs($this->hr)->put(route('clearance-items.update', $item), ['is_done' => 1])->assertRedirect();

        $item->refresh();
        $this->assertTrue($item->is_done);
        $this->assertSame($this->hr->id, $item->done_by);
        $this->assertNotNull($item->done_at);

        $this->actingAs($this->hr)->put(route('clearance-items.update', $item), ['is_done' => 0])->assertRedirect();

        $item->refresh();
        $this->assertFalse($item->is_done);
        $this->assertNull($item->done_by);
    }

    public function test_regularization_alert_notifies_hr(): void
    {
        $this->employee([
            'date_hired' => now()->subMonths(6)->addDays(5)->toDateString(),
            'probation_months' => 6, 'employment_status' => 'probationary',
        ]);

        $this->artisan('employees:regularization-alerts')->assertSuccessful();

        $this->assertSame(1, $this->hr->notifications()->count());
        $this->assertSame('Probationary employees due for regularization', $this->hr->notifications()->first()->data['title']);
    }

    public function test_regularization_alert_is_quiet_when_nobody_is_due(): void
    {
        $this->employee(['date_hired' => now()->toDateString(), 'employment_status' => 'probationary']);

        $this->artisan('employees:regularization-alerts')->assertSuccessful();

        $this->assertSame(0, $this->hr->notifications()->count());
    }

    public function test_a_separated_employee_cannot_be_separated_twice(): void
    {
        $employee = $this->employee(['employment_status' => 'separated', 'is_active' => false]);

        $this->actingAs($this->hr)->post(route('employees.separate', $employee))->assertSessionHas('error');

        $this->assertSame(0, ClearanceItem::count());
    }
}
