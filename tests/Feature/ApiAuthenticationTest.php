<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_through_api_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'ganadero@gantek.test',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ganadero@gantek.test',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'ganadero@gantek.test',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_invalid_credentials_cannot_generate_token(): void
    {
        User::factory()->create([
            'email' => 'ganadero@gantek.test',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ganadero@gantek.test',
            'password' => 'Incorrecta123!',
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_cannot_access_protected_api_route(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_protected_api_route(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_user_can_logout_and_current_token_is_deleted(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}