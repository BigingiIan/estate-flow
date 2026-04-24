<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Exception;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())
                        ->orWhere('google_id', $googleUser->getId())
                        ->first();

            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => Hash::make(Str::random(24)),
                    'google_id' => $googleUser->getId(),
                    'role'      => 'landlord',
                ]);
            } else {
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            }

            Auth::login($user, true);
            session()->regenerate();

            \Log::info('Google login success, user ID: ' . $user->id);

            return redirect()->route('dashboard');
        } catch (Exception $e) {
            \Log::error('Google callback error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }
}