<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), 900); // 15 min decay
            Log::channel('audit')->warning('login_failed', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $user = Auth::user();
        $user->last_active_at = now();
        $user->save();

        Log::channel('audit')->info('login_success', [
            'user_id' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectByRole($user));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            Log::channel('audit')->info('logout', [
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('login');
    }

    private function redirectByRole(User $user): string
    {
        return match ($user->role) {
            'admin' => route('dashboard'),
            'supervisor' => route('dashboard'),
            'agent' => route('stock.index'),
            default => route('dashboard'),
        };
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            Log::channel('audit')->warning('login_rate_limited', [
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey($request))]),
            ]);
        }
    }

    private function throttleKey(Request $request): string
    {
        return 'login:' . $request->ip();
    }
}
