<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            Log::error('Google SSO Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Đăng nhập bằng Google không thành công. Vui lòng thử lại!');
        }

        if (!$googleUser || !$googleUser->getEmail()) {
            return redirect()->route('login')->with('error', 'Không thể lấy thông tin tài khoản từ Google.');
        }

        // 1. Check if user already linked with this google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // 2. Check if user exists with the same email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google account to existing user
                $user->google_id = $googleUser->getId();
                if (empty($user->avatar)) {
                    $user->avatar = $googleUser->getAvatar();
                }
                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }
                $user->save();
            } else {
                // 3. Create a new user
                $name = $googleUser->getName();
                if (empty($name)) {
                    $name = explode('@', $googleUser->getEmail())[0];
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => null,
                ]);
            }
        } else {
            // Update avatar if changed or missing
            if ($googleUser->getAvatar() && $user->avatar !== $googleUser->getAvatar()) {
                $user->avatar = $googleUser->getAvatar();
                $user->save();
            }
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
