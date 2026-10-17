<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\HrisNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email,
            'password' => 'password', 'role' => $role, 'is_active' => true,
        ]);
    }

    private function leaveType(): LeaveType
    {
        return LeaveType::create(['name' => 'Vacation', 'code' => 'VL', 'is_paid' => true]);
    }

    public function test_filing_a_leave_request_notifies_approvers(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'user_id' => $employeeUser->id, 'is_active' => true,
        ]);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $this->actingAs($employeeUser)->post('/me/leave', [
            'leave_type_id' => $this->leaveType()->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-02',
        ])->assertRedirect();

        $this->assertSame(0, $employeeUser->unreadNotifications()->count());
        $this->assertSame(1, $hr->unreadNotifications()->count());
        $this->assertSame('Leave request', $hr->unreadNotifications->first()->data['title']);
    }

    public function test_a_decision_notifies_the_employee(): void
    {
        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'user_id' => $employeeUser->id, 'is_active' => true,
        ]);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType()->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-01',
            'days' => 1,
            'is_paid' => true,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($hr)->post('/leave-requests/'.$leave->id.'/decide', ['status' => 'approved'])->assertRedirect();

        $notification = $employeeUser->unreadNotifications->firstOrFail();

        $this->assertSame('Leave approved', $notification->data['title']);
        $this->assertSame(route('me.leave'), $notification->data['url']);
    }

    public function test_a_decision_also_emails_the_employee(): void
    {
        Notification::fake();

        $this->assertInstanceOf(ShouldQueue::class, new HrisNotification('Title', 'Body'));

        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        $employee = Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'user_id' => $employeeUser->id, 'is_active' => true,
        ]);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType()->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-01',
            'days' => 1,
            'is_paid' => true,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($hr)->post('/leave-requests/'.$leave->id.'/decide', ['status' => 'approved'])->assertRedirect();

        Notification::assertSentTo(
            $employeeUser,
            HrisNotification::class,
            fn ($notification) => in_array('mail', $notification->via($employeeUser), true),
        );
    }

    public function test_approver_notifications_are_not_emailed(): void
    {
        Notification::fake();

        $employeeUser = $this->user(User::ROLE_EMPLOYEE, 'emp@test.local');
        Employee::create([
            'employee_no' => 'E-001', 'first_name' => 'Juan', 'last_name' => 'Cruz',
            'user_id' => $employeeUser->id, 'is_active' => true,
        ]);
        $hr = $this->user(User::ROLE_HR, 'hr@test.local');

        $this->actingAs($employeeUser)->post('/me/leave', [
            'leave_type_id' => $this->leaveType()->id,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-02',
        ])->assertRedirect();

        Notification::assertSentTo(
            $hr,
            HrisNotification::class,
            fn ($notification) => ! in_array('mail', $notification->via($hr), true),
        );
    }

    public function test_notifications_can_be_read_one_by_one_or_all_at_once(): void
    {
        $user = $this->user(User::ROLE_HR, 'hr@test.local');

        $user->notify(new HrisNotification('First', 'one'));
        $user->notify(new HrisNotification('Second', 'two'));

        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('First');

        $first = $user->notifications()->latest()->firstOrFail();

        $this->actingAs($user)->get('/notifications/'.$first->id.'/read')->assertRedirect(route('notifications.index'));
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user)->post('/notifications/read-all')->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
