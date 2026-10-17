<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentComplianceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->seed(DocumentTypeSeeder::class);
        $this->hr = $this->user(User::ROLE_HR, 'hr@test.local');
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $this->hq->id,
        ]);
    }

    private function employee(string $no): Employee
    {
        return Employee::create([
            'employee_no' => $no, 'first_name' => 'Juan', 'last_name' => $no,
            'branch_id' => $this->hq->id, 'is_active' => true,
        ]);
    }

    public function test_employee_profile_lists_missing_required_documents(): void
    {
        $employee = $this->employee('E-001');

        $this->actingAs($this->hr)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('NBI Clearance')
            ->assertSee('Missing');
    }

    public function test_uploading_a_typed_document_records_compliance(): void
    {
        Storage::fake('public');

        $employee = $this->employee('E-002');
        $type = DocumentType::where('name', 'NBI Clearance')->firstOrFail();

        $this->actingAs($this->hr)->post(route('employees.documents.store', $employee), [
            'document_type_id' => $type->id,
            'title' => 'NBI 2026',
            'expires_at' => now()->addYear()->toDateString(),
            'file' => UploadedFile::fake()->create('nbi.pdf', 10),
        ])->assertRedirect();

        $document = EmployeeDocument::firstOrFail();

        $this->assertSame($type->id, $document->document_type_id);
        $this->assertSame('NBI Clearance', $document->type);
        $this->assertSame('ok', $document->expiryState());

        $row = $employee->fresh()->documentCompliance()->first(fn (array $row) => $row['type']->id === $type->id);
        $this->assertSame('ok', $row['state']);
    }

    public function test_expiring_document_type_requires_an_expiry_date(): void
    {
        Storage::fake('public');

        $employee = $this->employee('E-003');
        $type = DocumentType::where('name', 'NBI Clearance')->firstOrFail();

        $this->actingAs($this->hr)->post(route('employees.documents.store', $employee), [
            'document_type_id' => $type->id,
            'file' => UploadedFile::fake()->create('nbi.pdf', 10),
        ])->assertSessionHasErrors('expires_at');

        $this->assertDatabaseCount('employee_documents', 0);
    }

    public function test_compliance_report_lists_missing_documents(): void
    {
        $complete = $this->employee('E-004');
        $incomplete = $this->employee('E-005');

        foreach (DocumentType::required()->get() as $type) {
            $complete->documents()->create([
                'document_type_id' => $type->id, 'type' => $type->name,
                'title' => $type->name, 'file_path' => 'documents/fake.pdf',
            ]);
        }

        $this->actingAs($this->hr)->get('/documents?view=missing')
            ->assertOk()
            ->assertSee('E-005')
            ->assertDontSee('E-004');
    }

    public function test_compliance_report_lists_expiring_documents(): void
    {
        $soon = $this->employee('E-006');
        $later = $this->employee('E-007');
        $type = DocumentType::where('name', 'Medical Certificate')->firstOrFail();

        $soon->documents()->create([
            'document_type_id' => $type->id, 'type' => $type->name, 'title' => $type->name,
            'file_path' => 'documents/soon.pdf', 'expires_at' => now()->addDays(5)->toDateString(),
        ]);
        $later->documents()->create([
            'document_type_id' => $type->id, 'type' => $type->name, 'title' => $type->name,
            'file_path' => 'documents/later.pdf', 'expires_at' => now()->addDays(200)->toDateString(),
        ]);

        $this->actingAs($this->hr)->get('/documents')
            ->assertOk()
            ->assertSee('E-006')
            ->assertDontSee('E-007');
    }

    public function test_the_alert_command_notifies_hr(): void
    {
        $employee = $this->employee('E-008');
        $type = DocumentType::where('name', 'NBI Clearance')->firstOrFail();

        $employee->documents()->create([
            'document_type_id' => $type->id, 'type' => $type->name, 'title' => $type->name,
            'file_path' => 'documents/expired.pdf', 'expires_at' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('documents:alerts')->assertSuccessful();

        $this->assertSame(1, $this->hr->notifications()->count());
        $this->assertSame('201-file document compliance', $this->hr->notifications()->first()->data['title']);
    }

    public function test_the_alert_command_is_quiet_with_no_employees(): void
    {
        $this->artisan('documents:alerts')->assertSuccessful();

        $this->assertSame(0, $this->hr->notifications()->count());
    }

    public function test_non_hr_cannot_open_the_compliance_report(): void
    {
        $this->actingAs($this->user(User::ROLE_EMPLOYEE, 'emp@test.local'))->get('/documents')->assertForbidden();
    }
}
