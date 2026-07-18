<?php

/*
|--------------------------------------------------------------------------
| Global Helper Functions
|--------------------------------------------------------------------------
|
| Autoloaded by Composer (see the "files" entry in composer.json). Per
| CONVENTIONS.md §6.1, admin-configurable values are read through these helpers
| (never hardcode currency, date/time format, timezone, pagination size).
| Storage is the key-value `settings` table (SettingsSeeder seeds the defaults).
|
*/

use App\Models\Setting;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    /**
     * Read an admin-managed setting. Values are decoded by their declared type
     * (see Setting::getTypedValueAttribute). Each group is loaded once per request
     * and memoised; failures (no DB/migrations) fall back to the given default.
     *
     * @return mixed The decoded value, the whole group (when $key is null), or $default.
     */
    function setting(string $group, ?string $key = null, mixed $default = null): mixed
    {
        static $cache = [];

        if (! array_key_exists($group, $cache)) {
            try {
                $cache[$group] = Setting::query()
                    ->where('group', $group)
                    ->get()
                    ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typed_value])
                    ->all();
            } catch (\Throwable) {
                $cache[$group] = [];
            }
        }

        if ($key === null) {
            return $cache[$group] ?: $default;
        }

        return $cache[$group][$key] ?? $default;
    }
}

if (! function_exists('favicon_url')) {
    /**
     * URL of the admin-configured favicon (Settings → General), or null when none
     * is set so callers can fall back to the bundled default. Resolved once per
     * request; the stored value is the picked media's id.
     */
    function favicon_url(): ?string
    {
        static $resolved = false;
        static $url = null;

        if ($resolved) {
            return $url;
        }

        $resolved = true;
        $id = setting('general', 'favicon');
        $url = $id ? \App\Models\Media::find($id)?->url : null;

        return $url;
    }
}

if (! function_exists('logo_url')) {
    /**
     * The admin-configured website logo (Settings → General), or null when none
     * is set — callers fall back to the store name as text. Resolved once per
     * request. Shared by the storefront header and footer.
     */
    function logo_url(): ?string
    {
        static $resolved = false;
        static $url = null;

        if ($resolved) {
            return $url;
        }

        $resolved = true;
        $id = setting('general', 'logo');
        $url = $id ? \App\Models\Media::find($id)?->url : null;

        return $url;
    }
}

if (! function_exists('format_money')) {
    /**
     * Format an amount using the configured currency symbol, position, decimals
     * and separators (general group). e.g. "Rs 34,945.00".
     */
    function format_money(int|float|string|null $amount, ?int $decimals = null): string
    {
        $symbol = (string) setting('general', 'currency_symbol', 'Rs');
        $position = (string) setting('general', 'currency_position', 'left');
        $decimals ??= (int) setting('general', 'decimals', 2);
        $thousands = (string) setting('general', 'thousands_separator', ',');
        $point = (string) setting('general', 'decimal_separator', '.');

        $formatted = number_format((float) $amount, $decimals, $point, $thousands);

        return $position === 'right' ? "{$formatted} {$symbol}" : "{$symbol} {$formatted}";
    }
}

if (! function_exists('format_bytes')) {
    /**
     * Human-readable file size (e.g. 2.4 MB) from a byte count.
     */
    function format_bytes(int|float|string|null $bytes, int $precision = 1): string
    {
        $bytes = (float) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }
}

if (! function_exists('per_page')) {
    /**
     * The configured pagination size for web lists (CONVENTIONS §6.1).
     */
    function per_page(?int $default = null): int
    {
        return (int) setting('general', 'items_per_page', $default ?? 15);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date with the configured date format + timezone.
     */
    function format_date(mixed $date, ?string $format = null): string
    {
        if (empty($date)) {
            return '';
        }

        $format ??= (string) setting('general', 'date_format', 'd M Y');
        $tz = (string) setting('general', 'timezone', config('app.timezone'));

        return Carbon::parse($date)->timezone($tz)->format($format);
    }
}

if (! function_exists('format_time')) {
    /**
     * Format a time with the configured time format + timezone.
     */
    function format_time(mixed $date, ?string $format = null): string
    {
        if (empty($date)) {
            return '';
        }

        $format ??= (string) setting('general', 'time_format', 'h:i A');
        $tz = (string) setting('general', 'timezone', config('app.timezone'));

        return Carbon::parse($date)->timezone($tz)->format($format);
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Format a date + time with the configured formats + timezone.
     */
    function format_datetime(mixed $date): string
    {
        if (empty($date)) {
            return '';
        }

        $dateFormat = (string) setting('general', 'date_format', 'd M Y');
        $timeFormat = (string) setting('general', 'time_format', 'h:i A');
        $tz = (string) setting('general', 'timezone', config('app.timezone'));

        return Carbon::parse($date)->timezone($tz)->format("{$dateFormat} {$timeFormat}");
    }
}

if (! function_exists('delivery_method_label')) {
    /**
     * Human label for a delivery-method code (used on sales orders & purchases).
     * 'pickup' (collected in store) is treated as "no delivery" → null, so it never
     * clutters displays; only real deliveries get a label.
     */
    function delivery_method_label(?string $code): ?string
    {
        return match ($code) {
            'own_rider' => 'Own rider',
            'courier' => 'Third-party courier',
            'other' => 'Other person',
            default => null,
        };
    }
}

if (! function_exists('bill_format')) {
    /**
     * Resolve the print/bill format ('a4' | 'thermal'). A per-request `?format=`
     * choice wins; otherwise fall back to the store default (settings → Store).
     */
    function bill_format(?string $requested = null): string
    {
        if (in_array($requested, ['a4', 'thermal'], true)) {
            return $requested;
        }

        return setting('store', 'bill_type', 'a4') === 'thermal' ? 'thermal' : 'a4';
    }
}
