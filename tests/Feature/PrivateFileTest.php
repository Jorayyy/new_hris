<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCaseAction;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFileTest extends TestCase
{
    use RefreshDatabase;

    private Branch $hq;

    private Branch $ormoc;

    private User $hr;

    private User $manager;

    private User $foreignManager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->hq = Branch::create(['name' => 'Tacloban HQ', 'code' => 'TAC']);
        $this->ormoc = Branch::create(['name' => 'Ormoc Site', 'code' => 'ORM']);
        $this->hr = $this->user(User::ROLE_HR, 'hr@test.local', $this->hq);
        $this->manager = $this->user(User::ROLE_MANAGER, 'mgr@test.local', $this->hq);
        $this->foreignManager = $this->user(User::ROLE_MANAGER, 'mgr2@test.local', $this->ormoc);
    }

    private function user(string $role, string $email, ?Branch $branch = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email, 'password' => 'password',
            'role' => $role, 'is_active' => true, 'branch_id' => $branch?->id,
        ]);
    }

    private function employee(Branch $branch, ?User $user = null): Employee
    {
        return Employee::create([
            'employee_no' => 'E-'.random_int(1000, 9999),
            'first_name' => 'Juan', 'last_name' => 'Cruz',
            'branch_id' => $branch->id, 'user_id' => $user?->id, 'is_active' => true,
        ]);
    }

    public function test_uploaded_document_is_private_and_downloadable_by_hr(): void
    {
        $employee = $this->employee($this->hq);
        $type = DocumentType::create(['name' => 'NBI Clearance', 'is_required' => true, 'requires_expiry' => true]);

        $this->actingAs($this->hr)->post('/employees/'.$employee->id.'/documents', [
            'document_type_id' => $type->id,
            'title' => 'NBI Clearance 2026',
            'expires_at' => now()->addYear()->toDateString(),
            'file' => UploadedFile::fake()->create('nbi.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $document = $employee->documents()->firstOrFail();
        $this->assertStringStartsWith('documents/', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles('documents'));

        $this->actingAs($this->hr)->get(route('documents.file', $document))->assertOk();
    }

    public function test_document_download_is_hr_only(): void
    {
        $employee = $this->employee($this->hq);
        $document = $employee->documents()->create([
            'type' => 'NBI Clearance', 'title' => 'NBI', 'file_path' => 'documents/secret.pdf',
        ]);
        Storage::disk('local')->put('documents/secret.pdf', 'private');

        $this->actingAs($this->manager)->get(route('documents.file', $document))->assertForbidden();
        $this->actingAs($this->hr)->get(route('documents.file', $document))->assertOk();
    }

    public function test_missing_file_returns_not_found(): void
    {
        $employee = $this->employee($this->hq);
        $document = $employee->documents()->create([
            'type' => 'NBI Clearance', 'title' => 'NBI', 'file_path' => 'documents/not-there.pdf',
        ]);

        $this->actingAs($this->hr)->get(route('documents.file', $document))->assertNotFound();
    }

    public function test_case_attachment_is_private_and_branch_scoped(): void
    {
        $employee = $this->employee($this->hq);
        $case = DisciplinaryCase::create([
            'employee_id' => $employee->id, 'branch_id' => $this->hq->id,
            'type' => DisciplinaryCase::TYPE_INCIDENT, 'status' => DisciplinaryCase::STATUS_OPEN,
            'subject' => 'Late filing', 'incident_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->hr)->post('/cases/'.$case->id.'/notes', [
            'body' => 'Attaching the incident report.',
            'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $action = $case->actions()->firstOrFail();
        $this->assertStringStartsWith('cases/', $action->file_path);
        Storage::disk('local')->assertExists($action->file_path);

        $this->actingAs($this->hr)->get(route('cases.actions.file', $action))->assertOk();
        $this->actingAs($this->manager)->get(route('cases.actions.file', $action))->assertOk();
        $this->actingAs($this->foreignManager)->get(route('cases.actions.file', $action))->assertForbidden();
    }

    public function test_employee_can_download_their_own_case_attachment_only(): void
    {
        $owner = $this->user(User::ROLE_EMPLOYEE, 'owner@test.local', $this->hq);
        $other = $this->user(User::ROLE_EMPLOYEE, 'other@test.local', $this->hq);

        $employee = $this->employee($this->hq, $owner);
        $this->employee($this->hq, $other);

        $case = DisciplinaryCase::create([
            'employee_id' => $employee->id, 'branch_id' => $this->hq->id,
            'type' => DisciplinaryCase::TYPE_NTE, 'status' => DisciplinaryCase::STATUS_AWAITING,
            'subject' => 'Attendance', 'incident_date' => now()->toDateString(),
        ]);

        $action = $case->actions()->create([
            'user_id' => $this->hr->id, 'type' => DisciplinaryCaseAction::TYPE_FILED,
            'body' => 'NTE issued', 'file_path' => 'cases/nte.pdf',
        ]);
        Storage::disk('local')->put('cases/nte.pdf', 'private');

        $this->actingAs($owner)->get(route('me.cases.file', [$case->id, $action->id]))->assertOk();
        $this->actingAs($other)->get(route('me.cases.file', [$case->id, $action->id]))->assertNotFound();
    }
}
