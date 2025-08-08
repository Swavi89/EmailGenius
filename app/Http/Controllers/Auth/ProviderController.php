<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            $user = User::where([
                'provider_id' => $socialUser->id,
                'provider' => $provider
            ])->first();

            if ($user) {
                Auth::login($user);
                return redirect('/dashboard');
            }

            $existingUser = User::where('email', $socialUser->getEmail())->first();
            if ($existingUser && $existingUser->provider !== $provider) {
                return redirect('/login')->withErrors(['email' => "This email is already registered using {$existingUser->provider}. Please login with {$existingUser->provider} instead."]);
            }

            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->name,
                    'email' => $socialUser->email,
                    'provider_id' => $socialUser->id,
                    'provider' => $provider,
                    'provider_token' => $socialUser->token,
                ]);
            }
            Auth::login($user);
            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login');
        }
    }
}