<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class AuthenticationTest extends TestCase
{
    public function test_password_login_and_logout_are_preserved(): void
    {
        $user = User::factory()->create(['password' => 'TestPassword123!']);
        $this->get(route('login'))->assertOk();
        $this->post(route('login.process'), ['email' => $user->email, 'password' => 'TestPassword123!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_visible_and_login_is_rate_limited(): void
    {
        $email = 'bad@example.test';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->followingRedirects()->from(route('login'))->post(route('login.process'), ['email' => $email, 'password' => 'incorrect'])->assertOk()->assertSee('Correo o contraseña incorrectos.');
        }
        $this->post(route('login.process'), ['email' => $email, 'password' => 'incorrect'])->assertStatus(429);
    }

    public function test_social_login_does_not_overwrite_existing_password_or_grant_catalog_access(): void
    {
        $user = User::factory()->create(['password' => 'KeepThisPassword!', 'google_id' => 'demo-google-id']);
        $hash = $user->password;
        $socialUser = (new SocialUser)->map(['id' => 'demo-google-id', 'name' => 'Demo', 'email' => $user->email, 'avatar' => null]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertTrue(Hash::check('KeepThisPassword!', $user->fresh()->password));
        $this->assertFalse($user->fresh()->puede_gestionar_catalogos);
    }

    public function test_user_can_register_with_secure_password(): void
    {
        $response = $this->post(route('register.process'), [
            'name' => 'Usuario GanTek',
            'email' => 'nuevo@gantek.test',
            'password' => 'GanTek123',
            'password_confirmation' => 'GanTek123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'nuevo@gantek.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('GanTek123', $user->password));
        $this->assertFalse($user->puede_gestionar_catalogos);
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create([
            'email' => 'existente@gantek.test',
        ]);

        $response = $this->from(route('register'))->post(
            route('register.process'),
            [
                'name' => 'Usuario Prueba',
                'email' => 'existente@gantek.test',
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ]
        );

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'recuperacion@gantek.test',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@gantek.test',
            'password' => 'Anterior123',
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaGanTek123',
            'password_confirmation' => 'NuevaGanTek123',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertTrue(
            Hash::check(
                'NuevaGanTek123',
                $user->fresh()->password
            )
        );
    }
}
