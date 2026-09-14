<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        return $this->callback('google');
    }

    public function redirectToFacebook(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback(): RedirectResponse
    {
        return $this->callback('facebook');
    }

    private function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            $email = $socialUser->getEmail();
            if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route('login')->with('error', 'El proveedor no devolvió un correo válido.');
            }
            $column = $provider.'_id';
            $user = User::where($column, $socialUser->getId())->first()
                ?? User::firstOrNew(['email' => $email]);
            if ($user->exists && $user->{$column} && $user->{$column} !== $socialUser->getId()) {
                return redirect()->route('login')->with('error', 'La cuenta social no coincide con la cuenta registrada.');
            }
            if (! $user->exists) {
                $user->name = $socialUser->getName() ?: $email;
                $user->password = Str::random(64);
            }
            $user->{$column} = $socialUser->getId();
            $user->avatar = $socialUser->getAvatar();
            $user->save();
            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->route('dashboard');
        } catch (\Exception $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'No fue posible iniciar sesión con el proveedor.');
        }
    }
}
