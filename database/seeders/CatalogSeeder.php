<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo storefront catalog (§4/§5). Creates brands, categories and a set of
 * web-listed `trading` products — each with one default variant carrying price +
 * stock — mirroring the storefront's sample items so the public pages have real
 * data once they're wired to the database. Idempotent (updateOrCreate by slug/sku).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Brands -----------------------------------------------------------------
        $brands = [];
        foreach (['Electro', 'Apple', 'Dell', 'HP', 'Sony', 'Microsoft'] as $name) {
            $brands[$name] = Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        // Categories -------------------------------------------------------------
        $categoryNames = [
            'Laptops & Computers', 'Smart Phones', 'Headphones',
            'Audio Speakers', 'Cameras', 'Game Consoles', 'Accessories',
        ];
        $categories = [];
        foreach ($categoryNames as $i => $name) {
            $categories[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i, 'is_active' => true],
            );
        }

        // Products + default variants -------------------------------------------
        // [name, category, brand, retail, compare|null, featured]
        $items = [
            ['Wireless Audio System Multiroom 360', 'Audio Speakers', 'Sony', 2299, null, true],
            ['Tablet White EliteBook Revolve 810 G2', 'Laptops & Computers', 'HP', 1300, null, false],
            ['Purple Solo 2 Wireless', 'Headphones', 'Sony', 248, null, false],
            ['Tablet Red EliteBook Revolve 810 G2', 'Laptops & Computers', 'HP', 2100, 2299, false],
            ['White Solo 2 Wireless', 'Headphones', 'Sony', 249, null, false],
            ['Smartphone 6S 32GB LTE', 'Smart Phones', 'Apple', 1100, 1215, true],
            ['Apple MacBook Pro 13-inch M2 256GB', 'Laptops & Computers', 'Apple', 1299, null, true],
            ['Dell XPS 15 9520 i7 16GB 512GB', 'Laptops & Computers', 'Dell', 1948, null, false],
            ['HP Spectre x360 Convertible 14', 'Laptops & Computers', 'HP', 1499, null, false],
            ['Camera C430W 4k with Waterproof cover', 'Cameras', 'Sony', 782, null, false],
            ['Game Console Controller + USB 3.0', 'Game Consoles', 'Microsoft', 90, 99, false],
            ['Universal Headphones Case in Black', 'Accessories', 'Electro', 159, null, false],
        ];

        $author = User::query()->first(); // seeded admin — author for demo reviews

        foreach ($items as $i => [$name, $categoryName, $brandName, $retail, $compare, $featured]) {
            $sku = 'SKU-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'brand_id' => $brands[$brandName]->id ?? null,
                    'name' => $name,
                    'sku' => $sku,
                    'type' => Product::TYPE_TRADING,
                    'variant_mode' => Product::VARIANT_SIMPLE,
                    'is_purchasable' => true,
                    'is_sellable' => true,
                    'is_web_listed' => true,
                    'short_description' => "Premium {$categoryName} — {$name}.",
                    'description' => "The {$name} pairs premium build quality with everyday reliability — "
                        . "a standout pick in {$categoryName}.",
                    'specifications' => [
                        'General' => ['Brand' => $brandName, 'Model' => $name, 'Warranty' => '1 Year Manufacturer'],
                    ],
                    'base_price' => $retail,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'published_at' => now(),
                ],
            );

            ProductVariant::updateOrCreate(
                ['sku' => "{$sku}-D"],
                [
                    'product_id' => $product->id,
                    'cost' => round($retail * 0.7, 2),
                    'retail_price' => $retail,
                    'wholesale_price' => round($retail * 0.85, 2),
                    'compare_at_price' => $compare,
                    'stock_quantity' => 25,
                    'low_stock_threshold' => 5,
                    'is_active' => true,
                    'is_default' => true,
                ],
            );

            if ($author && $i < 3) {
                Review::updateOrCreate(
                    ['product_id' => $product->id, 'user_id' => $author->id],
                    [
                        'rating' => 5 - ($i % 2),
                        'title' => 'Great value',
                        'body' => 'Solid product, exactly as described. Would recommend.',
                        'is_approved' => true,
                        'verified_purchase' => true,
                    ],
                );
            }
        }

        // Default POS "Walk-in" customer (§10)
        Customer::updateOrCreate(
            ['name' => 'Walk-in Customer', 'user_id' => null],
            ['type' => Customer::TYPE_RETAIL, 'price_tier' => 'retail', 'is_active' => true],
        );
    }
}
