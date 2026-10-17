<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'User', 'email' => $role.'@test.local',
            'password' => 'password', 'role' => $role, 'is_active' => true,
        ]);
    }

    public function test_password_change_requires_a_number(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE);

        $this->actingAs($user)->put('/me/password', [
            'current_password' => 'password',
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_accepts_a_number(): void
    {
        $user = $this->user(User::ROLE_EMPLOYEE);

        $this->actingAs($user)->put('/me/password', [
            'current_password' => 'password',
            'password' => 'abcdefg1',
            'password_confirmation' => 'abcdefg1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('abcdefg1', $user->fresh()->password));
    }

    public function test_user_cannot_be_created_with_a_weak_password(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Newbie', 'email' => 'newbie@test.local',
            'password' => 'abcdefgh', 'role' => User::ROLE_EMPLOYEE, 'is_active' => '1',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'newbie@test.local']);
    }

    public function test_sessions_expire_after_thirty_idle_minutes(): void
    {
        $this->assertSame(30, config('session.lifetime'));
    }
}
