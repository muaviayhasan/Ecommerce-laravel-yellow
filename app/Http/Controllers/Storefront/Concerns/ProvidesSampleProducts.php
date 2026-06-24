<?php

namespace App\Http\Controllers\Storefront\Concerns;

use Illuminate\Support\Collection;

/**
 * PLACEHOLDER sample catalog data shared by the storefront controllers so the
 * theme renders before the real Products & Variants module exists. Replace the
 * callers with real queries (Product::active()->published()…) later.
 */
trait ProvidesSampleProducts
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function sampleProducts(): Collection
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
                'url' => route('product.show', \Illuminate\Support\Str::slug($item['name'])),
                'image' => "https://picsum.photos/seed/usman-shop-{$i}/400/400",
            ];
        });
    }
}
