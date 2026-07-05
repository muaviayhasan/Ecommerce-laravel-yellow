<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Product;
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
        ];

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

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if ((bool) setting('seo', 'indexable', true)) {
            $lines[] = 'Allow: /';
            foreach (['/admin', '/account', '/cart', '/checkout', '/wishlist', '/compare', '/login', '/register', '/support'] as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: ' . url('sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
