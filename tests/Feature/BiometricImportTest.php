<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\BiometricImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BiometricImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
        ]);
    }

    private function employee(string $no = 'E-001'): Employee
    {
        return Employee::create(['employee_no' => $no, 'first_name' => 'Juan', 'last_name' => 'Cruz']);
    }

    private function upload(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('device.csv', $contents);
    }

    public function test_parser_reads_paired_rows_with_a_header(): void
    {
        $service = new BiometricImportService;
        $path = tempnam(sys_get_temp_dir(), 'bio');
        file_put_contents($path, "employee_no,date,time_in,time_out\nE-001,2026-06-01,08:00,17:00\n");
        $result = $service->parse($path);
        unlink($path);

        $this->assertSame([], $result['problems']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('E-001', $result['rows'][0]['employee_no']);
        $this->assertSame('2026-06-01', $result['rows'][0]['date']);
        $this->assertSame('08:00', $result['rows'][0]['time_in']);
        $this->assertSame('17:00', $result['rows'][0]['time_out']);
    }

    public function test_parser_groups_one_row_per_punch_into_first_in_and_last_out(): void
    {
        $service = new BiometricImportService;
        $path = tempnam(sys_get_temp_dir(), 'bio');
        file_put_contents($path, implode("\n", [
            'User ID,Name,Date,Time,State',
            'E-001,Juan Cruz,2026-06-01,08:00,Check-in',
            'E-001,Juan Cruz,2026-06-01,12:05,Check-out',
            'E-001,Juan Cruz,2026-06-01,17:30,Check-out',
        ]));
        $result = $service->parse($path);
        unlink($path);

        $this->assertSame([], $result['problems']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('08:00', $result['rows'][0]['time_in']);
        $this->assertSame('17:30', $result['rows'][0]['time_out']);
    }

    public function test_parser_accepts_ph_dates_am_pm_times_and_combined_columns(): void
    {
        $service = new BiometricImportService;
        $path = tempnam(sys_get_temp_dir(), 'bio');
        file_put_contents($path, "E-002,15/01/2026,08.15.30 AM,2026-01-15 17:45:00\n");
        $result = $service->parse($path);
        unlink($path);

        $this->assertSame([], $result['problems']);
        $this->assertSame('2026-01-15', $result['rows'][0]['date']);
        $this->assertSame('08:15', $result['rows'][0]['time_in']);
        $this->assertSame('17:45', $result['rows'][0]['time_out']);
    }

    public function test_parser_reports_rows_it_cannot_read(): void
    {
        $service = new BiometricImportService;
        $path = tempnam(sys_get_temp_dir(), 'bio');
        file_put_contents($path, "E-003,2026-06-01,lunch\n");
        $result = $service->parse($path);
        unlink($path);

        $this->assertSame([], $result['rows']);
        $this->assertCount(1, $result['problems']);
        $this->assertSame('No recognizable time', $result['problems'][0]['reason']);
    }

    public function test_import_writes_attendance_and_reports_problems(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin)
            ->post('/biometric-devices/import', ['file' => $this->upload(implode("\n", [
                'employee_no,date,time_in,time_out',
                'E-001,2026-06-01,08:00,17:00',
                'GHOST,2026-06-01,08:00,17:00',
            ]))])
            ->assertRedirect()
            ->assertSessionHas('import_report', fn (array $report) => $report['created'] === 1 && $report['problem_count'] === 1);

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('biometric', $attendance->source);
        $this->assertSame('08:00', $attendance->time_in->format('H:i'));
        $this->assertSame('17:00', $attendance->time_out->format('H:i'));
    }

    public function test_reimport_updates_the_same_day_instead_of_duplicating(): void
    {
        $employee = $this->employee();
        $row = fn (string $out) => $this->upload("employee_no,date,time_in,time_out\nE-001,2026-06-01,08:00,{$out}\n");

        $this->actingAs($this->admin)->post('/biometric-devices/import', ['file' => $row('17:00')]);
        $this->actingAs($this->admin)
            ->post('/biometric-devices/import', ['file' => $row('18:30')])
            ->assertRedirect()
            ->assertSessionHas('import_report', fn (array $report) => $report['created'] === 0 && $report['updated'] === 1);

        $this->assertSame(1, Attendance::count());
        $this->assertSame('18:30', Attendance::firstOrFail()->time_out->format('H:i'));
    }

    public function test_import_rejects_a_missing_file(): void
    {
        $this->actingAs($this->admin)
            ->post('/biometric-devices/import')
            ->assertSessionHasErrors('file');
    }
}
