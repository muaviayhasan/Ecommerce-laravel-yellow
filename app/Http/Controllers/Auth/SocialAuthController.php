<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Support\SocialLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as OAuthUser;
use Spatie\Permission\Models\Role;

/**
 * Storefront (customer) social sign-in. Signs in an existing account matched by
 * provider or email; a first-time visitor gets a new customer account, mirroring
 * RegisterController@store. Which providers are available comes from the admin
 * "Social login" settings.
 */
class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(SocialLogin::enabled($provider), 404);

        return SocialLogin::driver($provider, route('social.callback', $provider))->redirect();
    }

    public function callback(string $provider, Request $request): RedirectResponse
    {
        abort_unless(SocialLogin::enabled($provider), 404);

        try {
            $oauth = SocialLogin::driver($provider, route('social.callback', $provider))->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Social sign-in failed. Please try again.');
        }

        $email = $oauth->getEmail();
        if (! $email) {
            return redirect()->route('login')->with('error', 'Your ' . ucfirst($provider) . ' account did not share an email address.');
        }

        $user = User::where('provider', $provider)->where('provider_id', $oauth->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user && ! $user->is_active) {
            return redirect()->route('login')->with('error', 'Your account is inactive. Please contact support.');
        }

        $user ??= $this->createCustomer($provider, $oauth, $email);

        // Link the provider (and refresh avatar / login stamp). A provider
        // sign-in also vouches for the email: if the account's address matches
        // the one the provider returned, treat it as verified — so the account
        // page never nags a Google/Facebook user to verify.
        $user->forceFill([
            'provider' => $provider,
            'provider_id' => $oauth->getId(),
            'avatar' => $user->avatar ?: $oauth->getAvatar(),
            'last_login_at' => now(),
            ...($user->email_verified_at === null && strcasecmp($user->email, $email) === 0
                ? ['email_verified_at' => now()]
                : []),
        ])->saveQuietly();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /** First-time social visitor → a users row + linked customer record + the customer role. */
    private function createCustomer(string $provider, OAuthUser $oauth, string $email): User
    {
        return DB::transaction(function () use ($provider, $oauth, $email): User {
            $user = User::create([
                'name' => $oauth->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'avatar' => $oauth->getAvatar(),
                'provider' => $provider,
                'provider_id' => $oauth->getId(),
                'email_verified_at' => now(), // the provider vouches for the email
                'is_active' => true,
            ]);

            $user->customer()->create([
                'name' => $user->name,
                'email' => $user->email,
                'type' => Customer::TYPE_RETAIL,
                'price_tier' => 'retail',
                'is_active' => true,
            ]);

            if (Role::where('name', 'customer')->where('guard_name', 'web')->exists()) {
                $user->assignRole('customer');
            }

            return $user;
        });
    }
}
