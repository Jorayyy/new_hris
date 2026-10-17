<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricDeviceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
        ]);
    }

    public function test_admin_registers_a_device_and_receives_a_token(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/biometric-devices', ['name' => 'Main Gate'])
            ->assertRedirect()
            ->assertSessionHas('new_device_token');

        $device = BiometricDevice::firstOrFail();
        $this->assertSame('Main Gate', $device->name);
        $this->assertSame(hash('sha256', session('new_device_token')), $device->token_hash);
    }

    public function test_admin_can_regenerate_and_disable(): void
    {
        $admin = $this->admin();
        $device = BiometricDevice::create(['name' => 'Gate', 'token_hash' => 'hash-1']);

        $this->actingAs($admin)->post('/biometric-devices/'.$device->id.'/regenerate')
            ->assertRedirect()
            ->assertSessionHas('new_device_token');
        $this->assertNotSame('hash-1', $device->fresh()->token_hash);

        $this->actingAs($admin)->put('/biometric-devices/'.$device->id, ['is_active' => 0])->assertRedirect();
        $this->assertFalse($device->fresh()->is_active);
    }
}
