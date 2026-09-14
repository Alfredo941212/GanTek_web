<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;

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
}
