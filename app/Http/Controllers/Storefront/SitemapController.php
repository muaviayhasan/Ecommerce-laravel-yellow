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

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if ((bool) setting('seo', 'indexable', true)) {
            $lines[] = 'Allow: /';
            foreach (['/admin', '/account', '/cart', '/checkout', '/wishlist', '/compare', '/login', '/register', '/support'] as $path) {
                $lines[] = 'Disallow: ' . $path;
            }

            // Filtered/searched/sorted views of /shop are deliberately NOT disallowed
            // here. They carry `noindex, follow` in the page head instead (see
            // shop.blade.php) — a crawler has to be able to fetch a page to see that
            // directive, and blocking it in robots.txt would leave any already-indexed
            // ones stuck in the index with no way to drop them.
            $lines[] = '';
            $lines[] = 'Sitemap: ' . url('sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
