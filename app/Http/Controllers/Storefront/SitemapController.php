<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use App\Support\IndexNow;
use Illuminate\Http\Response;

/** Dynamic sitemap.xml + robots.txt for search engines. */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('shop'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => route('blog'), 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => route('contact'), 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];

        // Category landing pages (only those with something to show).
        //
        // "Something to show" has to account for the sub-tree, not just direct
        // products. /shop?category=geysers lists everything filed under Instant,
        // Electric and Gas Geysers, but Geysers itself owns no products — so a
        // plain whereHas('products') dropped every parent department from the
        // sitemap, which is exactly the set the main nav links to. Walk each
        // category's descendants and keep it if anything in that sub-tree is listed.
        $active = Category::query()->where('is_active', true)->get(['id', 'slug', 'parent_id', 'updated_at', 'name']);
        $childrenOf = $active->groupBy('parent_id');

        $listedCounts = Product::webListed()
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $subtreeHasProducts = function (int $id) use (&$subtreeHasProducts, $childrenOf, $listedCounts): bool {
            if (($listedCounts[$id] ?? 0) > 0) {
                return true;
            }

            foreach ($childrenOf[$id] ?? [] as $child) {
                if ($subtreeHasProducts($child->id)) {
                    return true;
                }
            }

            return false;
        };

        foreach ($active->sortBy('name') as $c) {
            if (! $subtreeHasProducts($c->id)) {
                continue;
            }

            $urls[] = [
                'loc' => route('shop', ['category' => $c->slug]),
                'lastmod' => $c->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                // Departments (no parent) are the stronger landing pages of the two.
                'priority' => $c->parent_id === null ? '0.8' : '0.7',
            ];
        }

        // Live deals — real, indexable landing pages that were missing entirely.
        Deal::live()->whereHas('items')->orderByDesc('updated_at')->each(function (Deal $deal) use (&$urls) {
            $urls[] = [
                'loc' => route('deal.show', $deal->slug),
                'lastmod' => $deal->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        });

        Product::webListed()->orderByDesc('updated_at')->chunk(500, function ($rows) use (&$urls) {
            foreach ($rows as $p) {
                $urls[] = [
                    'loc' => route('product.show', $p->slug),
                    'lastmod' => $p->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
        });

        BlogPost::published()->orderByDesc('updated_at')->chunk(500, function ($rows) use (&$urls) {
            foreach ($rows as $post) {
                $urls[] = [
                    'loc' => route('blog.show', $post->slug),
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url>' . "\n"
                . '    <loc>' . e($u['loc']) . '</loc>' . "\n"
                . (empty($u['lastmod']) ? '' : '    <lastmod>' . $u['lastmod'] . '</lastmod>' . "\n")
                . '    <changefreq>' . $u['changefreq'] . '</changefreq>' . "\n"
                . '    <priority>' . $u['priority'] . '</priority>' . "\n"
                . '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * IndexNow ownership proof. The protocol wants a text file at /{key}.txt whose
     * only content is the key; serving it from a route means there is no file to
     * upload and nothing to go stale if the key ever changes.
     */
    public function indexNowKey(string $key): Response
    {
        abort_unless(hash_equals(IndexNow::key(), $key), 404);

        return response(IndexNow::key(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * llms.txt — a plain-Markdown map of the site for AI assistants, in the same
     * spirit as robots.txt but aimed at what should be *read* rather than what may
     * be crawled. Generated from the live catalogue so it can never drift from the
     * real category tree the way a hand-written file would.
     *
     * Worth having here specifically: ChatGPT's web results run on Bing, which the
     * IndexNow work already feeds, and "which air cooler should I buy in Lahore"
     * is exactly the shape of question an assistant answers rather than a SERP.
     */
    public function llms(): Response
    {
        $name = config('app.name');

        $lines = [
            '# ' . $name,
            '',
            '> ' . (setting('seo', 'meta_description')
                ?: 'Home appliances, electronics and batteries, with delivery across Pakistan.'),
            '',
            'Prices are in ' . setting('general', 'currency', 'PKR') . ' and shown on each product page.',
            'Stock and prices change; always read the product page rather than a cached copy.',
            '',
            '## Departments',
            '',
        ];

        Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')->orderBy('name')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->each(function (Category $root) use (&$lines) {
                $lines[] = '- [' . $root->name . '](' . route('shop', ['category' => $root->slug]) . ')';
                foreach ($root->children as $child) {
                    $lines[] = '  - [' . $child->name . '](' . route('shop', ['category' => $child->slug]) . ')';
                }
            });

        $lines = array_merge($lines, [
            '',
            '## Key pages',
            '',
            '- [All products](' . route('shop') . ')',
            '- [About](' . route('about') . ')',
            '- [Contact](' . route('contact') . ')',
            '- [Track an order](' . route('track.order') . ')',
            '- [Blog — buying guides](' . route('blog') . ')',
            '',
            '## Machine-readable',
            '',
            '- [Sitemap](' . url('sitemap.xml') . ') — every indexable URL',
            '- Product pages carry schema.org Product JSON-LD with price, availability and specifications.',
            '',
        ]);

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function robots(): Response
    {
        if (! (bool) setting('seo', 'indexable', true)) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        /*
         | Only /admin is blocked here, and that is deliberate.
         |
         | Disallow and noindex do not stack — they cancel. A crawler that obeys a
         | Disallow never fetches the page, so it never reads the noindex, and a URL
         | that was indexed before the rule existed (or that anyone links to) can sit
         | in the index indefinitely with no way to remove it. Google's own guidance
         | is explicit: to drop a page you must let it be crawled.
         |
         | /account, /cart, /checkout, /wishlist, /compare, /login and /register were
         | listed here AND carry `noindex, follow` in their own head, which is the
         | combination that guarantees neither works. They are removed so the noindex
         | can actually be read and honoured. They are a handful of cheap URLs on a
         | small site, so the crawl budget this costs is not worth the ambiguity.
         |
         | /support went too: the only routes under it are /admin/support/*, already
         | covered by /admin, so the rule matched nothing on the storefront.
         |
         | /admin stays. It is not an SEO problem to solve — there is no indexed admin
         | URL to retire — and there is no reason to invite crawling of that surface.
         | It answers 302 to a login for anyone unauthenticated in any case.
         |
         | Filtered/searched/sorted views of /shop are absent for the same reason as
         | the pages above: they carry noindex in the head (see shop.blade.php).
         */
        $disallow = ['/admin'];

        /*
         | AI crawlers are named and ALLOWED — a decision, not an oversight. Being read
         | by ChatGPT, Claude and Perplexity is how a shop gets mentioned when someone
         | asks "where do I buy a Dawlance washing machine in Lahore"; that is free
         | distribution rather than scraping to defend against. Naming them keeps the
         | policy visible and reversible in one place. Google-Extended governs Gemini
         | *training* only — it has no bearing on Google Search or AI Overviews.
         |
         | Each one needs its own copy of the Disallow list: a named group replaces the
         | `*` group for that bot rather than adding to it, so a bare "Allow: /" here
         | would hand GPTBot the admin panel and checkout. Built from the same array so
         | the two can never drift apart.
         */
        $agents = array_merge(['*'], ['GPTBot', 'ChatGPT-User', 'ClaudeBot', 'PerplexityBot', 'Google-Extended']);

        $lines = [];
        foreach ($agents as $agent) {
            $lines[] = 'User-agent: ' . $agent;
            $lines[] = 'Allow: /';
            foreach ($disallow as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
            $lines[] = '';
        }

        $lines[] = 'Sitemap: ' . url('sitemap.xml');

        // llms.txt is advertised as a COMMENT, not a directive. `Sitemap:` is the only
        // non-group field RFC 9309 defines; there is no registered `Llms:` field, and
        // emitting one makes Lighthouse report "robots.txt is not valid — unknown
        // directive". The llms.txt convention has no robots.txt field of its own, and
        // agents that want the file look for it at /llms.txt anyway.
        $lines[] = '# llms.txt: ' . url('llms.txt');

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
