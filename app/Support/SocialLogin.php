<?php

namespace App\Support;

use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

/**
 * Shared OAuth plumbing for the storefront (customer) and admin (staff) social
 * sign-in. Which providers are usable, and their credentials, come from the admin
 * "Social login" settings — falling back to the .env / services config when blank.
 */
class SocialLogin
{
    /** Providers wired to the "Social login" settings group. */
    public const PROVIDERS = ['google', 'facebook'];

    /** Usable only when enabled in settings AND a client id is available. */
    public static function enabled(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true)
            && (bool) setting('social_login', "{$provider}_enabled", false)
            && filled(self::credentials($provider)['client_id']);
    }

    /** @return array{client_id: ?string, client_secret: ?string} */
    public static function credentials(string $provider): array
    {
        [$idKey, $secretKey] = $provider === 'facebook'
            ? ['facebook_app_id', 'facebook_app_secret']
            : ['google_client_id', 'google_client_secret'];

        return [
            'client_id' => setting('social_login', $idKey) ?: config("services.{$provider}.client_id"),
            'client_secret' => setting('social_login', $secretKey) ?: config("services.{$provider}.client_secret"),
        ];
    }

    /** A Socialite driver pointed at $redirectUrl with the resolved credentials. */
    public static function driver(string $provider, string $redirectUrl): Provider
    {
        $creds = self::credentials($provider);

        config([
            "services.{$provider}.client_id" => $creds['client_id'],
            "services.{$provider}.client_secret" => $creds['client_secret'],
            "services.{$provider}.redirect" => $redirectUrl,
        ]);

        return Socialite::driver($provider);
    }
}
