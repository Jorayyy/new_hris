<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_system_health(): void
    {
        Cache::put('schedule_last_run', now()->subMinutes(2)->toIso8601String());

        $admin = $this->user(User::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)->get('/operations')
            ->assertOk()
            ->assertSee('Pending jobs')
            ->assertSee('Failed jobs')
            ->assertSee('Scheduler last run')
            ->assertSee('Configuration')
            ->assertDontSee('Retry all failed jobs');
    }

    public function test_non_admin_cannot_open_operations(): void
    {
        $this->actingAs($this->user(User::ROLE_HR))->get('/operations')->assertForbidden();
        $this->actingAs($this->user(User::ROLE_EMPLOYEE))->get('/operations')->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Ops '.$role,
            'email' => $role.'-ops@test.local',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
