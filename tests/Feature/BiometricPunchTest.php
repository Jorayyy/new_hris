<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricPunchTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-device-token-123456';

    protected function setUp(): void
    {
        parent::setUp();

        BiometricDevice::create([
            'name' => 'Gate',
            'token_hash' => BiometricDevice::hashToken(self::TOKEN),
        ]);
    }

    private function employee(): Employee
    {
        return Employee::create(['employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz']);
    }

    public function test_device_can_push_a_single_punch(): void
    {
        $employee = $this->employee();

        $this->postJson('/api/biometric/punch', [
            'employee_no' => 'E-001',
            'punch' => 'in',
            'timestamp' => '2026-06-01 08:00:00',
        ], ['X-Device-Token' => self::TOKEN])
            ->assertOk()
            ->assertJson(['ok' => true, 'processed' => 1, 'skipped' => 0]);

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('biometric', $attendance->source);
        $this->assertSame('08:00', $attendance->time_in->format('H:i'));
        $this->assertNotNull(BiometricDevice::first()->last_seen_at);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->postJson('/api/biometric/punch', [
            'employee_no' => 'E-001',
            'punch' => 'in',
        ], ['X-Device-Token' => 'wrong'])->assertUnauthorized();
    }

    public function test_device_can_push_a_batch(): void
    {
        $this->employee();

        $this->postJson('/api/biometric/punch', [
            'punches' => [
                ['employee_no' => 'E-001', 'punch' => 'in', 'timestamp' => '2026-06-01 08:00:00'],
                ['employee_no' => 'E-001', 'punch' => 'out', 'timestamp' => '2026-06-01 17:00:00'],
                ['employee_no' => 'GHOST', 'punch' => 'in'],
            ],
        ], ['X-Device-Token' => self::TOKEN])
            ->assertOk()
            ->assertJson(['processed' => 2, 'skipped' => 1]);

        $this->assertSame(1, Attendance::count());
    }
}
