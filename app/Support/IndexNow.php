<?php

namespace App\Support;

use App\Jobs\PingIndexNowJob;
use Illuminate\Support\Str;

/**
 * IndexNow (https://www.indexnow.org) — tells Bing, Yandex, Seznam and Naver that a
 * URL changed instead of waiting for them to re-crawl it. One submission is shared
 * between all participating engines.
 *
 * Google does not participate, so this changes nothing for Google Search; the
 * sitemap remains how Google finds updates. It is worth having anyway because Bing
 * powers ChatGPT's web results, which is a real source of traffic now.
 *
 * Ownership is proved by hosting a text file at /{key}.txt containing the key —
 * served by SitemapController::indexNowKey(), so there is no file to upload.
 *
 * The key lives in config, defaulting to a value derived from APP_KEY so a fresh
 * install has a stable, unguessable one without anybody generating it by hand.
 */
class IndexNow
{
    /** The site's IndexNow key. Deterministic per-install, overridable via env. */
    public static function key(): string
    {
        $configured = (string) config('services.indexnow.key', '');

        if ($configured !== '') {
            return $configured;
        }

        // Derived, not random: it must stay identical between requests and deploys,
        // because the key file has to keep matching what was submitted earlier.
        return substr(hash('sha256', (string) config('app.key')), 0, 32);
    }

    public static function enabled(): bool
    {
        return (bool) config('services.indexnow.enabled', false);
    }

    /**
     * Queue a submission for one or more URLs. Queued deliberately: this is an
     * outbound HTTP call, and the Reverb incident showed exactly what happens when
     * one of those sits inside a customer's request.
     *
     * @param  string|list<string>  $urls
     */
    public static function submit(string|array $urls): void
    {
        if (! self::enabled()) {
            return;
        }

        $urls = collect(is_array($urls) ? $urls : [$urls])
            ->filter(fn ($u) => filled($u) && Str::startsWith($u, ['http://', 'https://']))
            // The API rejects a submission containing any URL outside the host.
            ->filter(fn ($u) => parse_url($u, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST))
            ->unique()
            ->take(10000) // protocol ceiling per request
            ->values()
            ->all();

        if ($urls === []) {
            return;
        }

        PingIndexNowJob::dispatch($urls);
    }
}
