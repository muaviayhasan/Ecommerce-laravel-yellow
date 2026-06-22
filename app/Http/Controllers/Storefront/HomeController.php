<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Storefront home page.
     *
     * NOTE: product data here is PLACEHOLDER sample data so the theme renders
     * fully before the catalog module exists. Swap each collection for real
     * queries (e.g. Product::active()->published()->featured()->with('defaultVariant')…)
     * when Products & Variants are built (PROJECT_DOCUMENTATION build order #4).
     */
    public function index(): View
    {
        $pool = $this->sampleProducts();

        return view('storefront.home', [
            'featured' => $pool->take(6)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(6)->values(),
            'topRated' => $pool->sortByDesc('price')->take(6)->values(),
            'laptops' => $pool->values(), // showcased in a slider, chunked into slides in the view
            'trending' => $pool->shuffle()->take(6)->values(),
            'bestsellers' => $pool->take(6)->values(),
            'spotlight' => $pool->firstWhere('compare', '!=', null) ?? $pool->first(),
            'tvProducts' => $pool->values(), // Television Entertainment slider (chunked into slides in the view)
        ]);
    }

    /**
     * Generic "coming soon" placeholder for storefront pages not yet built.
     */
    public function placeholder(string $title): View
    {
        return view('storefront.placeholder', ['pageTitle' => $title]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sampleProducts(): Collection
    {
        $items = [
            ['name' => 'Wireless Audio System Multiroom 360', 'category' => 'Audio Speakers', 'price' => 2299, 'compare' => null],
            ['name' => 'Tablet White EliteBook Revolve 810 G2', 'category' => 'Laptops & Computers', 'price' => 1300, 'compare' => null],
            ['name' => 'Purple Solo 2 Wireless', 'category' => 'Headphones', 'price' => 248, 'compare' => null],
            ['name' => 'Tablet Red EliteBook Revolve 810 G2', 'category' => 'Laptops & Computers', 'price' => 2100, 'compare' => 2299],
            ['name' => 'White Solo 2 Wireless', 'category' => 'Headphones', 'price' => 249, 'compare' => null],
            ['name' => 'Smartphone 6S 32GB LTE', 'category' => 'Smart Phones', 'price' => 1100, 'compare' => 1215],
            ['name' => 'Apple MacBook Pro 13-inch M2 256GB', 'category' => 'Laptops & Computers', 'price' => 1299, 'compare' => null],
            ['name' => 'Dell XPS 15 9520 i7 16GB 512GB', 'category' => 'Laptops & Computers', 'price' => 1948, 'compare' => null],
            ['name' => 'HP Spectre x360 Convertible 14', 'category' => 'Laptops & Computers', 'price' => 1499, 'compare' => null],
            ['name' => 'Camera C430W 4k with Waterproof cover', 'category' => 'Cameras', 'price' => 782, 'compare' => null],
            ['name' => 'Game Console Controller + USB 3.0', 'category' => 'Game Consoles', 'price' => 90, 'compare' => 99],
            ['name' => 'Universal Headphones Case in Black', 'category' => 'Accessories', 'price' => 159, 'compare' => null],
        ];

        return collect($items)->map(function (array $item, int $i): array {
            return [
                ...$item,
                'url' => '#',
                'image' => "https://picsum.photos/seed/usman-shop-{$i}/400/400",
            ];
        });
    }
}
