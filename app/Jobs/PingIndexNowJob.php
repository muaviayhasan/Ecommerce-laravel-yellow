<?php

namespace App\Jobs;

use App\Support\IndexNow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Submits changed URLs to the IndexNow API so Bing/Yandex/Seznam/Naver re-crawl
 * them promptly. Fire-and-forget by design: search engines re-crawl on their own
 * schedule regardless, so a failed ping is a missed optimisation, never an error
 * worth surfacing to a customer or retrying aggressively.
 */
class PingIndexNowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Two attempts, then let it go — the sitemap still covers discovery. */
    public int $tries = 2;

    public int $timeout = 15;

    /** @param  list<string>  $urls */
    public function __construct(public array $urls)
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        if (! IndexNow::enabled() || $this->urls === []) {
            return;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);

        if (! $host || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return; // nothing to submit from a dev machine
        }

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->acceptJson()
                ->post('https://api.indexnow.org/IndexNow', [
                    'host' => $host,
                    'key' => IndexNow::key(),
                    'keyLocation' => rtrim(config('app.url'), '/') . '/' . IndexNow::key() . '.txt',
                    'urlList' => array_values($this->urls),
                ]);

            // 200 accepted, 202 accepted-pending-key-validation. Anything else is
            // worth a line in the log but not a thrown exception.
            if (! in_array($response->status(), [200, 202], true)) {
                Log::info('IndexNow submission rejected', [
                    'status' => $response->status(),
                    'urls' => count($this->urls),
                ]);
            }
        } catch (\Throwable $e) {
            Log::info('IndexNow submission failed: ' . $e->getMessage(), ['urls' => count($this->urls)]);
        }
    }
}
