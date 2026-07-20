<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

/**
 * Bridges admin-managed settings into Laravel's runtime config at boot. Today it
 * wires the "Mail" settings group into config('mail') so the SMTP transport and
 * "from" identity are configured from the admin panel (falling back to .env when
 * a value is blank). Booted from AppServiceProvider::boot().
 */
class SettingsApplier
{
    public static function apply(): void
    {
        self::applyGeneral();
        self::applyMail();
    }

    /**
     * The admin-configured store name becomes config('app.name'), so every page
     * title, OG tag and JSON-LD block uses it (instead of the .env APP_NAME).
     */
    private static function applyGeneral(): void
    {
        $general = rescue(fn () => Setting::groupWithDefaults('general'), [], report: false);

        if (filled($general['app_name'] ?? null)) {
            Config::set('app.name', $general['app_name']);
        }
    }

    /**
     * Push the stored "mail" settings into config('mail'). Resilient to a missing
     * settings table (fresh install / mid-migration) — it silently no-ops.
     */
    private static function applyMail(): void
    {
        $mail = rescue(fn () => Setting::groupWithDefaults('mail'), [], report: false);

        if (! $mail) {
            return;
        }

        // "From" identity — applies to every outgoing message.
        if (filled($mail['from_address'] ?? null)) {
            Config::set('mail.from.address', $mail['from_address']);
        }
        if (filled($mail['from_name'] ?? null)) {
            Config::set('mail.from.name', $mail['from_name']);
        }

        // SMTP transport — only override when a host is set; otherwise the
        // .env-driven config defaults stand untouched.
        if (blank($mail['host'] ?? null)) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $mail['host']);

        if (filled($mail['port'] ?? null)) {
            Config::set('mail.mailers.smtp.port', (int) $mail['port']);
        }

        Config::set('mail.mailers.smtp.username', $mail['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $mail['password'] ?? null);

        // Admins pick TLS / SSL / None; translate to the Symfony transport scheme.
        // SSL (implicit TLS, port 465) → "smtps"; TLS/None → null so STARTTLS is
        // negotiated opportunistically (port 587).
        $scheme = match ($mail['encryption'] ?? null) {
            'ssl' => 'smtps',
            default => null,
        };
        Config::set('mail.mailers.smtp.scheme', $scheme);
    }
}
