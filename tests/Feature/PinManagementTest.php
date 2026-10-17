<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinManagementTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::create([
            'name' => 'HR', 'email' => 'hr@test.local',
            'password' => 'password', 'role' => User::ROLE_HR, 'is_active' => true,
        ]);
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'is_active' => true,
        ], $attributes));
    }

    public function test_hr_can_set_a_pin_that_unlocks_punching(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->hr())
            ->put('/pins/'.$employee->id, ['pin' => '4821'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('4821', $employee->fresh()->pin));

        $this->post('/time-clock/punch', ['employee_no' => 'E-001', 'pin' => '4821', 'punch' => 'in'])
            ->assertSessionHas('punch_ok');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_employee_without_a_pin_cannot_punch(): void
    {
        $this->employee();

        $this->post('/time-clock/punch', ['employee_no' => 'E-001', 'pin' => '0000', 'punch' => 'in'])
            ->assertSessionHas('punch_error');
    }

    public function test_repeated_wrong_pins_lock_the_employee_and_hr_can_clear_it(): void
    {
        $employee = $this->employee(['pin' => '4821']);

        for ($i = 0; $i < Employee::PIN_MAX_ATTEMPTS; $i++) {
            $this->post('/time-clock/punch', ['employee_no' => 'E-001', 'pin' => '0000', 'punch' => 'in']);
        }

        $this->assertTrue($employee->fresh()->isPinLocked());

        $this->post('/time-clock/punch', ['employee_no' => 'E-001', 'pin' => '4821', 'punch' => 'in'])
            ->assertSessionHas('punch_error', fn ($message) => str_contains($message, 'Too many failed attempts'));

        $this->assertSame(0, Attendance::where('employee_id', $employee->id)->count());

        $this->actingAs($this->hr())->put('/pins/'.$employee->id, ['pin' => ''])->assertRedirect();

        $this->assertFalse($employee->fresh()->isPinLocked());
        $this->assertNull($employee->fresh()->pin);
    }

    public function test_generate_shows_a_new_pin_once(): void
    {
        $employee = $this->employee(['pin' => '1111']);

        $response = $this->actingAs($this->hr())->post('/pins/'.$employee->id.'/generate')->assertRedirect();

        $pin = $response->getSession()->get('generated_pin')['pin'];

        $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);
        $this->assertTrue(Hash::check($pin, $employee->fresh()->pin));
    }

    public function test_employee_cannot_manage_pins(): void
    {
        $employee = $this->employee();
        $user = User::create([
            'name' => 'Emp', 'email' => 'emp@test.local',
            'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/pins')->assertForbidden();
        $this->actingAs($user)->put('/pins/'.$employee->id, ['pin' => '4821'])->assertForbidden();
    }
}
