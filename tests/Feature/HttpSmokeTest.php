<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\GovernmentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HttpSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_admin_can_open_core_pages(): void
    {
        $this->seed(GovernmentTableSeeder::class);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
        ]);

        foreach (['/', '/employees', '/attendance', '/attendance/corrections', '/government', '/government/remittances', '/payroll/periods', '/payroll/runs', '/payroll/final-pay', '/loans', '/loan-types', '/users', '/audit-log', '/time-clock/access', '/biometric-devices', '/leave-balances', '/shifts', '/roster', '/swaps', '/ot', '/schedule-changes', '/reports/sss-r3', '/reports/philhealth-rf1', '/reports/pagibig-mcrf', '/reports/bir-1601c', '/reports/bir-2316'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_exports_and_self_service_pages(): void
    {
        $this->seed(GovernmentTableSeeder::class);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
        ]);

        foreach (['/employees/export', '/attendance/export', '/leave-requests/export', '/reports/bir-1601c?csv=1'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        }

        $employee = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);

        $this->actingAs($employee)->get('/me/profile')->assertOk()->assertSee('Change password');

        $this->actingAs($employee)->put('/me/password', [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-secret-123', $employee->fresh()->password));
    }

    public function test_role_dashboards_render(): void
    {
        $this->seed(GovernmentTableSeeder::class);

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_HR, User::ROLE_PAYROLL, User::ROLE_MANAGER] as $role) {
            $user = User::create([
                'name' => ucfirst($role), 'email' => $role.'@test.local',
                'password' => 'password', 'role' => $role, 'is_active' => true,
            ]);

            $this->actingAs($user)->get('/')->assertOk();
        }
    }

    public function test_employee_cannot_reach_payroll(): void
    {
        $employee = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);

        $this->actingAs($employee)->get('/payroll/periods')->assertForbidden();
        $this->actingAs($employee)->get('/employees')->assertForbidden();
    }

    public function test_payroll_run_csv_export(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Jan 1-15', 'date_from' => '2026-01-01', 'date_to' => '2026-01-15',
            'pay_date' => '2026-01-20', 'frequency' => 'semi_monthly', 'status' => 'open',
        ]);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'draft']);

        $this->actingAs($admin)
            ->get('/payroll/runs/'.$run->id.'/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');
    }
}
