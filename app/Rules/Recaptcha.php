<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google reCAPTCHA v3 (invisible, score-based) — verifies the token posted by
 * the <x-recaptcha> Blade component.
 *
 * Fails when Google says the token is invalid, when the token's action doesn't
 * match the expected one (stops a token minted on one form being replayed on
 * another), or when the score is below services.recaptcha.min_score.
 *
 * Fails OPEN when Google is unreachable — a network blip must never block a
 * real customer's quote request. Low-score rejections are logged at info level
 * so the threshold can be tuned from real traffic.
 *
 * Use Recaptcha::rules('action') in controllers: it returns the full rule set
 * when keys are configured and a no-op when they aren't, so local/staging
 * (and the test suite) work without keys.
 */
class Recaptcha implements ValidationRule
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(private string $action)
    {
    }

    /**
     * Rule set for a form's recaptcha_token field — no-op when keys are absent
     * (mirrors the component, which renders nothing without a site key).
     *
     * @return list<string|ValidationRule>
     */
    public static function rules(string $action): array
    {
        return self::enabled()
            ? ['required', 'string', new self($action)]
            : ['nullable', 'string'];
    }

    public static function enabled(): bool
    {
        return (string) config('services.recaptcha.site_key') !== ''
            && (string) config('services.recaptcha.secret') !== '';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $result = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => config('services.recaptcha.secret'),
                'response' => (string) $value,
                'remoteip' => request()->ip(),
            ])->throw()->json();
        } catch (Throwable $e) {
            // Fail open: Google being unreachable must not block real customers.
            Log::warning('reCAPTCHA siteverify unreachable — allowing request', [
                'action' => $this->action,
                'ip' => request()->ip(),
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! is_array($result) || ! ($result['success'] ?? false)) {
            $fail('Verification failed — please refresh the page and try again.');

            return;
        }

        if (($result['action'] ?? null) !== $this->action) {
            $fail('Verification failed — please refresh the page and try again.');

            return;
        }

        $score = (float) ($result['score'] ?? 0);
        $minScore = (float) config('services.recaptcha.min_score', 0.5);

        if ($score < $minScore) {
            Log::info('reCAPTCHA low score rejected', [
                'score' => $score,
                'min_score' => $minScore,
                'action' => $this->action,
                'ip' => request()->ip(),
            ]);

            $fail('We couldn’t verify this request. Please try again in a moment.');
        }
    }
}
