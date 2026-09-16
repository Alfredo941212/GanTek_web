<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_registrarse_desde_la_api(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Usuario App',
            'email' => 'usuarioapp@gantek.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
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
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Usuario App')
            ->assertJsonPath('user.email', 'usuarioapp@gantek.test');

        $this->assertDatabaseHas('users', [
            'name' => 'Usuario App',
            'email' => 'usuarioapp@gantek.test',
        ]);

        $user = User::where('email', 'usuarioapp@gantek.test')->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123', $user->password),
        );

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_correo_no_puede_registrarse_dos_veces(): void
    {
        User::factory()->create([
            'email' => 'repetido@gantek.test',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Usuario Repetido',
            'email' => 'repetido@gantek.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_registro_requiere_datos_obligatorios(): void
    {
        $response = $this->postJson('/api/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);
    }

    public function test_confirmacion_de_contrasena_debe_coincidir(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Usuario App',
            'email' => 'usuarioapp@gantek.test',
            'password' => 'Password123',
            'password_confirmation' => 'OtraPassword123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_usuario_registrado_puede_acceder_a_ruta_protegida(): void
    {
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Usuario App',
            'email' => 'usuarioapp@gantek.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $registerResponse->assertCreated();

        $token = $registerResponse->json('token');

        $response = $this
            ->withToken($token)
            ->getJson('/api/dashboard');

        $response->assertOk();
    }
}