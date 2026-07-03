<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

/**
 * Staff-only sign-in for the admin panel. Same credential flow as the storefront
 * login, but rejects anyone without a system role (website customers can't get in),
 * and offers Microsoft SSO restricted to existing staff accounts.
 */
class AdminAuthController extends Controller
{
    /** Max failed attempts before lockout, per identifier+IP. */
    private const MAX_ATTEMPTS = 5;

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isStaff()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('home');
        }

        return view('admin.auth.login');
    }

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

        // Valid credentials — but the admin panel is for staff only.
        if (! $request->user()->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'auth' => 'This account is not authorised to access the admin panel.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    // ---- Microsoft SSO (staff only) -----------------------------------------

    public function redirectToMicrosoft(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function microsoftCallback(Request $request): RedirectResponse
    {
        try {
            $oauth = Socialite::driver('microsoft')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.login')
                ->withErrors(['auth' => 'Microsoft sign-in failed. Please try again.']);
        }

        // Only authenticate an EXISTING active staff account — never create one here,
        // otherwise anyone with a Microsoft account could get in.
        $user = User::where('provider', 'microsoft')->where('provider_id', $oauth->getId())->first()
            ?? User::where('email', $oauth->getEmail())->first();

        if (! $user || ! $user->is_active || ! $user->isStaff()) {
            return redirect()->route('admin.login')
                ->withErrors(['auth' => 'This Microsoft account is not authorised for admin access.']);
        }

        $user->forceFill([
            'provider' => 'microsoft',
            'provider_id' => $oauth->getId(),
            'avatar' => $user->avatar ?: $oauth->getAvatar(),
            'last_login_at' => now(),
        ])->saveQuietly();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    // ---- helpers ------------------------------------------------------------

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
        return 'admin|' . Str::transliterate(Str::lower((string) $request->input('identifier')) . '|' . $request->ip());
    }
}
