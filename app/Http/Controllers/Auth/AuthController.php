<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Max failed attempts before lockout, per identifier+IP. */
    private const MAX_ATTEMPTS = 5;

    /**
     * Show the login form (bounce home if already signed in).
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Attempt to authenticate. The identifier may be an email or a phone number.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureNotRateLimited($request);

        $identifier = (string) $request->input('identifier');
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $authenticated = Auth::attempt([
            $field => $identifier,
            'password' => $request->input('password'),
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'auth' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $user = $request->user();
        $user->last_login_at = now();
        $user->saveQuietly();

        return redirect()->intended(route('home'));
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Block further attempts once the per-identifier+IP limit is hit.
     */
    private function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'auth' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('identifier')) . '|' . $request->ip());
    }
}
