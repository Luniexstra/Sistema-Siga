<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SecurityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Admin12345'),
        ]);

        $response = $this->actingAs($user)->put(route('security.password.update'), [
            'current_password' => 'Admin12345',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('NuevaClave123', $user->fresh()->password));
    }

    public function test_a_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@test.com',
            'password' => Hash::make('Admin12345'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NuevaClave123', $user->fresh()->password));
    }
}
