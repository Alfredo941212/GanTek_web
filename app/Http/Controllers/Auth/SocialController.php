<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    // Redirigir a Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback de Google
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName(),
                    'google_id' => $socialUser->getId(),
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    'email_verified_at' => now(),
                ]
            );
            
            auth()->login($user);
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Error al iniciar sesión con Google');
        }
    }

    // Redirigir a Facebook
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    // Callback de Facebook
    public function handleFacebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->user();
            
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName(),
                    'facebook_id' => $socialUser->getId(),
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    'email_verified_at' => now(),
                ]
            );
            
            auth()->login($user);
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Error al iniciar sesión con Facebook');
        }
    }
}