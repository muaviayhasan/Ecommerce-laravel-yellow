<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ProvidesSampleProducts;

    /**
     * Product detail page.
     *
     * Design-only: PLACEHOLDER product enriched with a gallery + features.
     * Replace with Product::where('slug', $slug)->with('variants','media','reviews')
     * ->firstOrFail() and a real related-products query when the catalog module lands.
     */
    public function show(string $slug): View
    {
        $pool = $this->sampleProducts();
        $base = $pool->first();

        $product = array_merge($base, [
            'categories' => 'Smart Phones & Tablets, Smartphones, Laptops & Computers',
            'availability' => 'In stock',
            'features' => [
                'Fingertip controls: on-speaker volume and bass.',
                'Handy headphone jack — listen to music, movies and games in total privacy.',
                'Long battery life with fast charging support.',
            ],
            'gallery' => [
                $base['image'],
                'https://picsum.photos/seed/usman-pd-2/600/600',
                'https://picsum.photos/seed/usman-pd-3/600/600',
                'https://picsum.photos/seed/usman-pd-4/600/600',
            ],
            // ----- Description tab -----
            'description_intro' => 'A flagship 17-inch powerhouse built for creators and gamers alike — the '
                . $base['name'] . ' pairs a high-refresh Full-HD display with the latest discrete graphics so '
                . 'every frame stays crisp and tear-free.',
            'description_body' => [
                'Under the hood, a 12th-generation Intel Core i7 works alongside 16 GB of fast DDR5 memory and a '
                    . '512 GB NVMe SSD, giving you near-instant boot times and plenty of headroom for heavy '
                    . 'multitasking, editing and play.',
                'A precision-milled aluminium chassis keeps it light at 2.6 kg, while the 70 Wh battery and rapid '
                    . 'charging mean you can comfortably leave the adapter behind for the day.',
            ],
            'highlights' => [
                '17.3" Full-HD 144 Hz IPS display',
                'Intel Core i7 (12th Gen) processor',
                'NVIDIA GeForce RTX 4060 graphics',
                '16 GB DDR5 RAM, expandable to 32 GB',
                '512 GB ultra-fast NVMe SSD',
                'Wi-Fi 6E + Bluetooth 5.3',
            ],
            // ----- Specification tab (grouped) -----
            'specifications' => [
                'General' => [
                    'Brand' => 'Electro',
                    'Model Number' => 'Y700-17 GF790',
                    'Release Year' => '2025',
                    'In The Box' => 'Laptop, 230W adapter, quick-start guide',
                    'Warranty' => '1 Year Manufacturer Warranty',
                ],
                'Display' => [
                    'Screen Size' => '17.3 inches',
                    'Resolution' => '1920 × 1080 (Full HD)',
                    'Panel Type' => 'IPS, LED-backlit',
                    'Refresh Rate' => '144 Hz',
                ],
                'Performance' => [
                    'Processor' => 'Intel Core i7-12700H (12th Gen)',
                    'Memory' => '16 GB DDR5 (up to 32 GB)',
                    'Storage' => '512 GB NVMe SSD',
                    'Graphics' => 'NVIDIA GeForce RTX 4060 8 GB',
                ],
                'Connectivity & Power' => [
                    'Ports' => '1× USB-C, 3× USB-A, HDMI 2.1, RJ-45, 3.5 mm',
                    'Wireless' => 'Wi-Fi 6E, Bluetooth 5.3',
                    'Battery' => '70 Wh, up to 8 hours',
                    'Weight' => '2.6 kg',
                ],
            ],
        ]);

        return view('storefront.product', [
            'product' => $product,
            'accessories' => $pool->slice(2, 4)->values(),
            'related' => $pool->slice(1, 4)->values(),
            'moreProducts' => $pool->slice(6, 4)->values(),
            'latest' => $pool->slice(1, 3)->values(),
            'featured' => $pool->take(2)->values(),
            'topSelling' => $pool->slice(10, 2)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(1)->values(),
        ]);
    }
}
