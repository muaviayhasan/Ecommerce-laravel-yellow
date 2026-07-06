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
        foreach (['Dawlance', 'PEL', 'Boss', 'Orient', 'Super Asia', 'GFC', 'Haier', 'Homage', 'Kenwood', 'Waves'] as $name) {
            $brands[$name] = Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        // Categories -------------------------------------------------------------
        // A single "Electronics" root groups every appliance family. Geysers,
        // Coolers, Fans and Home Appliances each nest their own type-specific
        // children beneath it (parent → group → leaves).
        $categoryTree = [
            'Electronics' => [
                'Coolers'         => ['Air Cooler', 'Water Cooler'],
                'Geysers'         => ['Instant Geysers', 'Electric Geysers', 'Gas Geysers'],
                'Fans'            => ['AC Fans', 'DC Fans'],
                'Home Appliances' => ['Washing Machine', 'Water Dispenser', 'Stoves'],
                'Solar Plates'    => [],
            ],
        ];

        $categories = [];
        $makeCategory = function (string $name, ?int $parentId, int $order) use (&$categories) {
            return $categories[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'parent_id' => $parentId, 'sort_order' => $order, 'is_active' => true],
            );
        };

        $rootOrder = 0;
        foreach ($categoryTree as $rootName => $groups) {
            $root = $makeCategory($rootName, null, $rootOrder++);
            $groupOrder = 0;
            foreach ($groups as $groupName => $leaves) {
                $group = $makeCategory($groupName, $root->id, $groupOrder++);
                $leafOrder = 0;
                foreach ($leaves as $leafName) {
                    $makeCategory($leafName, $group->id, $leafOrder++);
                }
            }
        }

        // Products + default variants -------------------------------------------
        // [name, category, brand, retail, compare|null, featured]
        $items = [
            ['Super Asia Room Air Cooler ECM-4000', 'Air Cooler', 'Super Asia', 32999, 35999, true],
            ['Boss Room Air Cooler ECM-9000 Icy Cool', 'Air Cooler', 'Boss', 28999, null, false],
            ['Waves Electric Water Cooler 65L', 'Water Cooler', 'Waves', 74999, null, false],
            ['Dawlance Automatic Washing Machine DWT-260', 'Washing Machine', 'Dawlance', 66999, 72999, true],
            ['Haier Twin Tub Washing Machine HWM-120', 'Washing Machine', 'Haier', 38999, null, false],
            ['Orient 3-Tap Water Dispenser Icon', 'Water Dispenser', 'Orient', 45999, null, false],
            ['GFC Ceiling Fan Deluxe 56 inch', 'AC Fans', 'GFC', 8999, 9999, false],
            ['PEL DC Inverter Ceiling Fan SmartSaver', 'DC Fans', 'PEL', 12999, null, true],
            ['Homage Solar Panel 550W Mono PERC', 'Solar Plates', 'Homage', 21999, null, true],
            ['Boss Instant Gas Geyser 6L', 'Instant Geysers', 'Boss', 15999, null, false],
            ['PEL Electric Storage Geyser 30 Gallon', 'Electric Geysers', 'PEL', 33999, 36999, false],
            ['Super Asia Gas Geyser 35 Gallon', 'Gas Geysers', 'Super Asia', 27999, null, false],
            ['Kenwood 5-Burner Gas Stove Crystal', 'Stoves', 'Kenwood', 18999, null, false],
            ['Dawlance Microwave Oven MD-9 Grill', 'Home Appliances', 'Dawlance', 24999, 27999, false],
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
