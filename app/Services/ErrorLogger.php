<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Persists unhandled exceptions to the error_logs table for review in the admin.
 * Deduplicates by a class+file+line fingerprint (a recurring error increments a
 * counter and re-opens if it was resolved), skips "expected" exceptions
 * (validation, auth, 4xx, …), redacts sensitive input, and — crucially — never
 * throws, so a logging failure can't take down the request that raised the error.
 *
 * Wired into bootstrap/app.php via $exceptions->report().
 */
class ErrorLogger
{
    /** Expected exceptions that are noise, not bugs — never stored. */
    private const SKIP = [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Symfony\Component\Routing\Exception\RouteNotFoundException::class,
    ];

    /** Request keys whose values must never be stored. */
    private const REDACT = [
        'password', 'password_confirmation', 'current_password', '_token',
        'token', 'secret', 'api_key', 'google_maps_key', 'card', 'cvv', 'cvc',
    ];

    public function log(Throwable $e): void
    {
        try {
            if (! $this->enabled() || $this->shouldSkip($e)) {
                return;
            }

            $request = app()->runningInConsole() ? null : request();
            $fingerprint = hash('sha256', get_class($e) . '|' . $e->getFile() . '|' . $e->getLine());

            $existing = ErrorLog::where('fingerprint', $fingerprint)->first();

            if ($existing) {
                $existing->forceFill([
                    'occurrences' => $existing->occurrences + 1,
                    'last_seen_at' => now(),
                    'message' => $this->message($e),
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                    'user_id' => $this->userId(),
                    'ip_address' => $request?->ip(),
                    'resolved_at' => null, // it happened again — re-open it
                    'resolved_by' => null,
                ])->save();

                return;
            }

            ErrorLog::create([
                'fingerprint' => $fingerprint,
                'level' => $e instanceof \Error ? 'critical' : 'error',
                'type' => get_class($e),
                'message' => $this->message($e),
                'code' => ((string) $e->getCode()) !== '0' ? (string) $e->getCode() : null,
                'file' => Str::limit($e->getFile(), 1000, ''),
                'line' => $e->getLine(),
                'url' => $request ? Str::limit($request->fullUrl(), 2040, '') : null,
                'method' => $request?->method(),
                'user_id' => $this->userId(),
                'ip_address' => $request?->ip(),
                'context' => $this->context($request),
                'trace' => Str::limit($e->getTraceAsString(), 60000, ''),
                'occurrences' => 1,
                'last_seen_at' => now(),
            ]);
        } catch (Throwable $inner) {
            // Logging must never break the app; degrade to the file log and move on.
            try {
                logger()->warning('ErrorLogger could not persist an exception: ' . $inner->getMessage());
            } catch (Throwable) {
                // give up silently
            }
        }
    }

    private function enabled(): bool
    {
        try {
            return (bool) setting('system', 'log_errors', true);
        } catch (Throwable) {
            return false;
        }
    }

    private function shouldSkip(Throwable $e): bool
    {
        foreach (self::SKIP as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        // Expected HTTP errors (404, 403, 419, 429, …) aren't bugs; only 5xx are.
        return $e instanceof HttpExceptionInterface && $e->getStatusCode() < 500;
    }

    private function message(Throwable $e): string
    {
        return Str::limit($e->getMessage(), 60000, '') ?: '(no message)';
    }

    private function userId(): ?int
    {
        try {
            return auth()->id();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    private function context(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        try {
            $input = collect($request->except(self::REDACT))
                ->take(50)
                ->map(fn ($value) => is_scalar($value) ? Str::limit((string) $value, 500) : $value)
                ->all();

            return $input ? ['input' => $input] : null;
        } catch (Throwable) {
            return null;
        }
    }
}
