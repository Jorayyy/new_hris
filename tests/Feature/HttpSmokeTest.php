<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\GovernmentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        foreach (['/', '/employees', '/attendance', '/government', '/payroll/periods', '/payroll/runs', '/users'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
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
}
