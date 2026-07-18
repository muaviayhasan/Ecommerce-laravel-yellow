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
        $brandNames = [
            'Dawlance', 'PEL', 'Boss', 'Orient', 'Super Asia', 'GFC', 'Haier', 'Homage', 'Kenwood', 'Waves',
            // Brands introduced by the extended catalog below.
            'National', 'Panasonic', 'Asia', 'Pak', 'Pak Gas', 'BGC', 'Choice', 'Ideal',
            'Glam Gas', 'Global', 'Shanghai', 'Super Gree', 'Super Fire Gas',
            'RAF', 'Sogo', 'Longi',
        ];
        $brands = [];
        foreach ($brandNames as $name) {
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
                'Coolers'            => ['Air Cooler', 'Water Cooler'],
                'Geysers'            => ['Instant Geysers', 'Electric Geysers', 'Gas Geysers'],
                'Fans'               => ['AC Fans', 'DC Fans', 'Solar Fans'],
                'Home Appliances'    => ['Washing Machine', 'Water Dispenser', 'Stoves', 'Microwave Ovens', 'Irons', 'Blenders & Juicers'],
                'Kitchen Appliances' => ['Built-In Hobs', 'Kitchen Hoods', 'Cooking Ranges', 'Cooktops'],
                'Gas Appliances'     => ['LPG Cylinders', 'Patio Heaters', 'Gas Regulators'],
                'Solar Plates'       => [],
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

            // Keyed on SKU (the stable positional identity) rather than slug, so
            // renaming a demo item updates it in place instead of colliding on
            // the unique SKU index.
            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'slug' => Str::slug($name),
                    'category_id' => $categories[$categoryName]->id,
                    'brand_id' => $brands[$brandName]->id ?? null,
                    'name' => $name,
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

        // Extended catalog -------------------------------------------------------
        // Real Pakistani-market products with researched specs, highlights and
        // realistic PKR pricing. Each row carries the full storefront payload
        // (short/long description, grouped specs, highlights, warranty, flags).
        // SKUs continue after the demo items above. A null brand => unbranded.
        $catalog = $this->extendedCatalog();

        $skuOffset = count($items);
        foreach ($catalog as $j => $row) {
            $sku = 'SKU-' . str_pad((string) ($skuOffset + $j + 1), 4, '0', STR_PAD_LEFT);
            $retail = $row['retail'];
            $compare = $row['compare'] ?? null;
            $brandName = $row['brand'] ?? null;

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'slug' => Str::slug($row['name']),
                    'category_id' => $categories[$row['category']]->id,
                    'brand_id' => $brandName ? ($brands[$brandName]->id ?? null) : null,
                    'name' => $row['name'],
                    'type' => Product::TYPE_TRADING,
                    'variant_mode' => Product::VARIANT_SIMPLE,
                    'is_purchasable' => true,
                    'is_sellable' => true,
                    'is_web_listed' => true,
                    'short_description' => $row['short_description'],
                    'description' => $row['description'],
                    'highlights' => $row['highlights'],
                    'specifications' => $row['specifications'],
                    'warranty' => $row['warranty'] ?? null,
                    'base_price' => $retail,
                    'is_active' => true,
                    'is_featured' => $row['featured'] ?? false,
                    'is_trending' => $row['trending'] ?? false,
                    'is_bestseller' => $row['bestseller'] ?? false,
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
        }

        // Default POS "Walk-in" customer (§10)
        Customer::updateOrCreate(
            ['name' => 'Walk-in Customer', 'user_id' => null],
            ['type' => Customer::TYPE_RETAIL, 'price_tier' => 'retail', 'is_active' => true],
        );
    }

    /**
     * Extended, real-world Pakistani-market catalog. Specs, highlights and PKR
     * pricing were researched per product; keys map 1:1 to the loop in run().
     * `brand` may be null for genuinely unbranded/generic items.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extendedCatalog(): array
    {
        return [
            // ---- Gas cylinders, iron & washing machines ----------------------
            [
                'name' => 'Pak Gas Domestic LPG Gas Cylinder (11.8 kg)',
                'category' => 'LPG Cylinders',
                'brand' => 'Pak Gas',
                'retail' => 4500,
                'compare' => null,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'OGRA-standard 11.8 kg domestic steel LPG cylinder for everyday household cooking, supplied empty.',
                'description' => 'The Pak Gas 11.8 kg LPG cylinder is the standard domestic gas cylinder used in Pakistani households for cooking and water heating. It is manufactured from heavy-gauge welded steel with a protective valve guard and a leak-tested brass valve for safe daily use. The cylinder is supplied empty and can be filled at any authorised LPG distributor. Its full-capacity 11.8 kg size is the most common household size across Pakistan.',
                'warranty' => 'No warranty',
                'highlights' => [
                    'Standard 11.8 kg domestic household size',
                    'Heavy-gauge welded steel body',
                    'Protective valve guard collar',
                    'Leak-tested brass valve',
                    'OGRA-compliant construction',
                    'Supplied empty, refillable anywhere',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Domestic LPG Cylinder', 'Gas Capacity' => '11.8 kg', 'Fill State' => 'Supplied empty', 'Valve' => 'Brass POL/clip-on valve', 'Colour' => 'Grey / Silver'],
                    'Physical' => ['Body Material' => 'Welded steel', 'Tare Weight' => 'Approx. 13 kg', 'Height' => 'Approx. 58 cm', 'Diameter' => 'Approx. 30 cm'],
                ],
            ],
            [
                'name' => 'National Inverter Electric Dry Iron NR-17',
                'category' => 'Irons',
                'brand' => 'National',
                'retail' => 3499,
                'compare' => 4200,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Lightweight 1000W dry iron with a non-stick coated soleplate and adjustable thermostat for crisp, everyday pressing.',
                'description' => 'The National NR-17 is a compact 1000W electric dry iron built for reliable daily use in Pakistani homes. It features a smooth non-stick coated soleplate that glides over fabric and an adjustable thermostat dial with settings for cotton, silk and synthetics. An indicator light and automatic temperature control help prevent scorching, while the lightweight body reduces arm fatigue during long ironing sessions. It is a budget-friendly, energy-efficient choice for basic pressing needs.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '1000W fast-heating element',
                    'Non-stick coated soleplate',
                    'Adjustable thermostat dial',
                    'Temperature indicator light',
                    'Lightweight, easy-grip handle',
                    '360-degree swivel cord',
                ],
                'specifications' => [
                    'General' => ['Model' => 'NR-17', 'Type' => 'Dry Iron', 'Soleplate' => 'Non-stick coated', 'Colour' => 'Black / Blue'],
                    'Power' => ['Wattage' => '1000 W', 'Voltage' => '220-240 V, 50 Hz', 'Thermostat' => 'Adjustable with indicator light'],
                    'Physical' => ['Weight' => 'Approx. 0.9 kg', 'Cord' => '360-degree swivel, approx. 1.8 m'],
                ],
            ],
            [
                'name' => 'Asia Single Tub Semi-Automatic Washing Machine SA-210',
                'category' => 'Washing Machine',
                'brand' => 'Asia',
                'retail' => 19500,
                'compare' => 21500,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => '8 kg single-tub semi-automatic washer with a copper motor and rust-proof plastic body for economical family laundry.',
                'description' => 'The Asia SA-210 is an entry-level 8 kg single-tub semi-automatic washing machine designed for everyday household washing. It uses a durable copper-winding motor paired with a strong pulsator to deliver a thorough wash while keeping running costs low. The shock- and rust-proof plastic body, built-in lint filter and wash timer make it simple and dependable to operate. Its compact footprint suits small to medium-sized families and apartments.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '8 kg wash capacity',
                    '100% copper motor',
                    'Shock & rust-proof plastic body',
                    'Strong wash pulsator',
                    'Built-in lint filter',
                    'Manual wash timer with buzzer',
                ],
                'specifications' => [
                    'General' => ['Model' => 'SA-210', 'Type' => 'Single Tub Semi-Automatic', 'Body Material' => 'Rust-proof plastic', 'Colour' => 'White / Grey'],
                    'Capacity & Performance' => ['Wash Capacity' => '8 kg', 'Wash Timer' => '0-15 minutes', 'Pulsator' => 'Single storm pulsator', 'Lint Filter' => 'Yes'],
                    'Power' => ['Motor' => 'Copper winding', 'Wash Power' => 'Approx. 300 W', 'Voltage' => '220-240 V, 50 Hz'],
                    'Physical' => ['Weight' => 'Approx. 9 kg', 'Dimensions' => 'Approx. 46 x 45 x 74 cm'],
                ],
            ],
            [
                'name' => 'Asia Classic Wash Single Tub Washing Machine SA-220',
                'category' => 'Washing Machine',
                'brand' => 'Asia',
                'retail' => 22500,
                'compare' => 24500,
                'featured' => false, 'trending' => false, 'bestseller' => false,
                'short_description' => '10 kg single-tub washer with heavy-gear copper motor, double pulsator and easy-wash tray for larger household loads.',
                'description' => 'The Asia Classic Wash SA-220 is a 10 kg single-tub washing machine that steps up capacity and wash power for growing families. It combines a powerful copper motor with heavy-gear technology and a double storm pulsator to handle heavier and larger loads with ease. The shock- and rust-proof double plastic body, easy-wash tray, lint filter and end-of-cycle buzzer add everyday convenience. A water-drain knob makes emptying quick and mess-free.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '10 kg wash capacity',
                    'Heavy-gear copper motor',
                    'Double storm pulsator',
                    'Shock & rust-proof double plastic body',
                    'Easy-wash tray & lint filter',
                    'End-of-cycle buzzer',
                ],
                'specifications' => [
                    'General' => ['Model' => 'SA-220', 'Series' => 'Classic Wash', 'Type' => 'Single Tub Semi-Automatic', 'Body Material' => 'Double plastic, rust-proof', 'Colour' => 'White'],
                    'Capacity & Performance' => ['Wash Capacity' => '10 kg', 'Pulsator' => 'Double storm pulsator', 'Wash System' => 'Heavy-gear technology', 'Lint Filter' => 'Yes'],
                    'Power' => ['Motor' => '100% copper', 'Wash Power' => 'Approx. 350 W', 'Voltage' => '220-240 V, 50 Hz'],
                    'Physical' => ['Weight' => 'Approx. 10 kg', 'Dimensions' => 'Approx. 50 x 48 x 82 cm', 'Water Drain' => 'Knob-controlled'],
                ],
            ],
            [
                'name' => 'Asia Super Wash Single Tub Washing Machine SA-240',
                'category' => 'Washing Machine',
                'brand' => 'Asia',
                'retail' => 25999,
                'compare' => 28500,
                'featured' => true, 'trending' => true, 'bestseller' => true,
                'short_description' => '10 kg single-tub Super Wash washer with shower-wash technology, powerful copper motor and energy-saving operation.',
                'description' => 'The Asia Super Wash SA-240 is a 10 kg single-tub washing machine that adds shower-wash technology for deeper, more even cleaning. A powerful copper motor drives a double storm pulsator and scrub board to lift out tough stains, while the shock- and rust-proof double plastic body ensures long-term durability. Practical features include an easy-wash tray, lint filter, end-of-cycle buzzer and water-drain knob. Energy-saving operation keeps electricity and water use in check for busy households.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '10 kg wash capacity',
                    'Shower wash technology',
                    'Powerful copper motor',
                    'Double storm pulsator & scrub board',
                    'Shock & rust-proof double plastic body',
                    'Energy-saving operation',
                ],
                'specifications' => [
                    'General' => ['Model' => 'SA-240', 'Series' => 'Super Wash', 'Type' => 'Single Tub Semi-Automatic', 'Body Material' => 'Double plastic, rust-proof', 'Colour' => 'White / Blue'],
                    'Capacity & Performance' => ['Wash Capacity' => '10 kg', 'Wash Technology' => 'Shower wash', 'Pulsator' => 'Double storm pulsator with scrub board', 'Lint Filter' => 'Yes', 'Buzzer' => 'End-of-cycle buzzer'],
                    'Power' => ['Motor' => '100% copper', 'Wash Power' => 'Approx. 350 W', 'Voltage' => '220-240 V, 50 Hz', 'Energy' => 'Energy-saving mode'],
                    'Physical' => ['Weight' => 'Approx. 10.5 kg', 'Dimensions' => 'Approx. 52 x 50 x 84 cm', 'Water Drain' => 'Knob-controlled'],
                ],
            ],
            [
                'name' => 'Asia Smart Wash Steel Body Single Tub Washing Machine SA-260',
                'category' => 'Washing Machine',
                'brand' => 'Asia',
                'retail' => 30500,
                'compare' => 33000,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => '12 kg stainless-steel body single-tub washer with turbo copper motor, big pulsator and low-noise, rust-free operation.',
                'description' => 'The Asia Smart Wash SA-260 is a premium 12 kg single-tub washing machine built around a durable stainless-steel body that resists rust and stays looking new for years. Its 100% copper turbo motor and heavy-duty big pulsator deliver fast, powerful low-noise washing for large family loads. Energy-saving technology cuts electricity consumption by up to 40%, while the built-in buzzer, lint filter and water-drain system add day-to-day convenience. The steel construction makes it one of the most robust single-tub washers in its class.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '12 kg large wash capacity',
                    'Stainless steel rust-free body',
                    '100% copper turbo motor',
                    'Heavy-duty big pulsator',
                    'Up to 40% energy saving',
                    'Low-noise operation',
                ],
                'specifications' => [
                    'General' => ['Model' => 'SA-260', 'Series' => 'Smart Wash', 'Type' => 'Single Tub Semi-Automatic', 'Body Material' => 'Stainless steel', 'Colour' => 'Steel / Silver'],
                    'Capacity & Performance' => ['Wash Capacity' => '12 kg', 'Pulsator' => 'Heavy-duty big pulsator', 'Wash System' => 'Low-noise turbo wash', 'Lint Filter' => 'Yes', 'Buzzer' => 'Built-in buzzer'],
                    'Power' => ['Motor' => '100% copper turbo', 'Wash Power' => 'Approx. 420 W', 'Voltage' => '220-240 V, 50 Hz', 'Energy' => 'Up to 40% energy saving'],
                    'Physical' => ['Weight' => 'Approx. 12 kg', 'Dimensions' => 'Approx. 55 x 52 x 86 cm', 'Water Drain' => 'Knob-controlled'],
                ],
            ],
            [
                'name' => 'Pak Copper Rust Proof Metal Body Washing Machine PK-980',
                'category' => 'Washing Machine',
                'brand' => 'Pak',
                'retail' => 24000,
                'compare' => 26500,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => '10 kg single-tub washer with 100% copper motor and a rust-proof powder-coated metal body for heavy-duty daily washing.',
                'description' => 'The Pak PK-980 is a 10 kg single-tub washing machine built around a sturdy rust-proof powder-coated metal body for extra durability. Its 100% copper motor and strong pulsator provide reliable washing power for large family loads while running efficiently on modest electricity. Convenience features include a wash timer, lint filter, end-of-cycle buzzer and a water-drain outlet. The heavy metal construction gives it stability and a long service life in demanding household use.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '10 kg wash capacity',
                    '100% copper motor',
                    'Rust-proof powder-coated metal body',
                    'Strong wash pulsator',
                    'Built-in lint filter & buzzer',
                    'Energy-efficient operation',
                ],
                'specifications' => [
                    'General' => ['Model' => 'PK-980', 'Type' => 'Single Tub Semi-Automatic', 'Body Material' => 'Powder-coated rust-proof metal', 'Colour' => 'White / Grey'],
                    'Capacity & Performance' => ['Wash Capacity' => '10 kg', 'Wash Timer' => '0-15 minutes', 'Pulsator' => 'Storm pulsator', 'Lint Filter' => 'Yes'],
                    'Power' => ['Motor' => '100% copper', 'Wash Power' => 'Approx. 360 W', 'Voltage' => '220-240 V, 50 Hz'],
                    'Physical' => ['Weight' => 'Approx. 11 kg', 'Dimensions' => 'Approx. 50 x 48 x 82 cm', 'Water Drain' => 'Outlet with knob'],
                ],
            ],
            [
                'name' => 'Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)',
                'category' => 'LPG Cylinders',
                'brand' => 'BGC',
                'retail' => 12000,
                'compare' => 13500,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => 'OGRA-approved 10 kg composite fiber LPG cylinder, nearly 50% lighter than steel, explosion-free and rust-proof.',
                'description' => 'The BGC Composite Fiber LPG cylinder is a modern 10 kg gas cylinder made from high-strength fiberglass and HDPE, making it nearly 50% lighter than a traditional steel cylinder at around 5.5 kg tare weight. It is explosion-free by design, corrosion-free and UV and weather resistant, so it withstands sun, rain and temperature changes without rusting. A translucent body lets users see the remaining gas level at a glance, improving safety and convenience. It is OGRA-approved and manufactured to national and international safety standards.',
                'warranty' => '5 Year Brand Warranty',
                'highlights' => [
                    '10 kg composite fiber construction',
                    'Nearly 50% lighter than steel (approx. 5.5 kg tare)',
                    'Explosion-free, safe design',
                    'Corrosion-free & rust-proof',
                    'UV and weather resistant',
                    'Translucent body shows gas level',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Composite Fiber LPG Cylinder', 'Gas Capacity' => '10 kg', 'Valve' => '22 mm compact valve', 'Approval' => 'OGRA-approved', 'Colour' => 'Multicolour'],
                    'Physical' => ['Body Material' => 'Fiberglass & HDPE composite', 'Tare Weight' => 'Approx. 5.5 kg (with valve)', 'Height' => 'Approx. 55 cm', 'Diameter' => 'Approx. 30 cm', 'Features' => 'Translucent, UV & weather resistant'],
                ],
            ],

            // ---- Microwaves, water dispensers & built-in hobs ----------------
            [
                'name' => 'Haier 20 Litre Solo Microwave Oven (HDL-20MXP7)',
                'category' => 'Microwave Ovens',
                'brand' => 'Haier',
                'retail' => 17500,
                'compare' => 20000,
                'featured' => true, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Compact 20L solo microwave with 700W power, 6 power levels and mechanical controls for quick reheating and defrosting.',
                'description' => 'The Haier HDL-20MXP7 is a 20-litre solo microwave oven rated at 700 watts, ideal for reheating, defrosting and simple cooking in small to medium households. It offers 6 microwave power levels, speed and weight defrost, and easy-to-use mechanical rotary controls. Housed in a durable white cabinet with an electroplated handle and internal light, it is an energy-efficient everyday kitchen companion.',
                'warranty' => '2 Years Brand Warranty',
                'highlights' => [
                    '20 Litre cavity capacity',
                    '700W output power',
                    '6 microwave power levels',
                    'Speed & weight defrost',
                    'Mechanical rotary controls',
                    'Internal light & alarm signal',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Solo Microwave Oven', 'Colour' => 'White', 'Control' => 'Mechanical (Rotary)', 'Model' => 'HDL-20MXP7'],
                    'Capacity & Performance' => ['Cavity Capacity' => '20 Litres', 'Power Levels' => '6', 'Defrost' => 'Speed & Weight Defrost', 'Cooking Guides' => '3'],
                    'Power' => ['Output Power' => '700 W', 'Voltage / Frequency' => '220-240V, 50Hz'],
                    'Physical' => ['Dimensions (W x D x H)' => '308 x 300 x 193 mm'],
                ],
            ],
            [
                'name' => 'PEL Table-Top Classic 115 Water Dispenser',
                'category' => 'Water Dispenser',
                'brand' => 'PEL',
                'retail' => 23500,
                'compare' => 27000,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Compact 2-tap table-top water dispenser delivering hot and normal water in an elegant white finish.',
                'description' => 'The PEL Table-Top Classic 115 (PWD-115 TT) is a space-saving countertop water dispenser designed for homes and offices where floor space is limited. It provides both hot and normal water through two easy-access taps, with a 3.7-litre cold tank and 1.2-litre hot tank giving roughly 4.9 litres of total capacity. Built with a high-quality plastic body and stainless steel internal tank, it offers reliable low-noise, energy-efficient operation.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    'Compact table-top design',
                    '2 taps: hot & normal water',
                    '3.7L cold + 1.2L hot tank',
                    '304 stainless steel inner tank',
                    'Low-noise energy-efficient cooling',
                    'Elegant white finish',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Table-Top Water Dispenser', 'Colour' => 'White', 'Taps' => '2 (Hot & Normal)', 'Model' => 'PWD-115 TT'],
                    'Capacity & Performance' => ['Total Capacity' => '4.9 Litres', 'Cold Tank' => '3.7 Litres', 'Hot Tank' => '1.2 Litres'],
                    'Power' => ['Voltage / Frequency' => '220-240V, 50Hz'],
                    'Physical' => ['Body Material' => 'High-quality plastic with metal accents', 'Inner Tank' => '304 Stainless Steel'],
                ],
            ],
            [
                'name' => 'Dawlance 20 Litre Solo Microwave Oven (DW-MD 15 Solo White)',
                'category' => 'Microwave Ovens',
                'brand' => 'Dawlance',
                'retail' => 14900,
                'compare' => 18999,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Reliable 20L solo microwave with 700W power, 5 power levels and a 30-minute timer in a clean white body.',
                'description' => 'The Dawlance DW-MD 15 is a 20-litre solo microwave oven built for everyday reheating and cooking. It delivers 700 watts of power with 5 selectable power levels, a 30-minute cooking timer and simple mechanical rotary controls. Finished in white with a stainless steel interior, it is a durable and budget-friendly choice from one of Pakistan\'s most trusted appliance brands.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '20 Litre large capacity',
                    '700W output power',
                    '5 power level settings',
                    '30-minute cooking timer',
                    'Mechanical rotary controls',
                    'Durable white finish',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Solo Microwave Oven', 'Colour' => 'White', 'Control' => 'Mechanical (Rotary)', 'Model' => 'DW-MD 15'],
                    'Capacity & Performance' => ['Cavity Capacity' => '20 Litres', 'Power Levels' => '5', 'Timer' => '30 Minutes'],
                    'Power' => ['Output Power' => '700 W', 'Voltage / Frequency' => '220-240V, 50Hz'],
                    'Physical' => ['Interior' => 'Stainless Steel'],
                ],
            ],
            [
                'name' => 'Choice Appliances Floor-Standing Water Dispenser with Built-In Mini Refrigerator',
                'category' => 'Water Dispenser',
                'brand' => 'Choice',
                'retail' => 38000,
                'compare' => 44000,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => '3-tap floor-standing dispenser serving hot, cold and normal water with a handy lower mini-fridge cabinet.',
                'description' => 'The Choice Appliances floor-standing water dispenser combines three taps for hot, cold and normal water with a built-in mini refrigerator cabinet at the base for storing snacks and beverages. A high-efficiency compressor delivers rapid cooling and heating, while a double safety device guards against overheating. Practical LED indicators, a child-safety lock on the hot tap and low-noise operation make it a complete solution for homes and offices.',
                'warranty' => '1 Year Brand Warranty (Compressor Extended)',
                'highlights' => [
                    '3 taps: hot, cold & normal',
                    'Built-in lower mini refrigerator',
                    'High-efficiency compressor cooling',
                    'Child-safety lock on hot tap',
                    'Double safety anti-overheat device',
                    'LED indicators & low-noise operation',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Floor-Standing Water Dispenser', 'Colour' => 'White / Silver', 'Taps' => '3 (Hot, Cold & Normal)', 'Refrigerator' => 'Built-in Mini Fridge Cabinet'],
                    'Capacity & Performance' => ['Cooling' => 'Compressor Cooling', 'Heating Capacity' => '5-6 L/hr', 'Cooling Capacity' => '2-3 L/hr'],
                    'Power' => ['Voltage / Frequency' => '220-240V, 50Hz', 'Heating Power' => '500 W', 'Cooling Power' => '100 W'],
                    'Physical' => ['Cabinet' => 'Lower Refrigerated Storage Compartment', 'Body Material' => 'Metal Cabinet with Plastic Top'],
                ],
            ],
            [
                'name' => 'Choice Appliances Premium Tri-Series 3-Burner Built-In Gas Hob (GL 308 DG BR Golden)',
                'category' => 'Built-In Hobs',
                'brand' => 'Choice',
                'retail' => 34000,
                'compare' => 40000,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Premium 3-burner built-in glass hob with brass burners, auto-ignition and an elegant golden tempered-glass top.',
                'description' => 'The Choice Appliances Tri-Series GL 308 DG BR is a premium 3-burner built-in gas hob topped with toughened golden tempered glass for a striking modern kitchen look. It features durable brass burners, auto electric ignition and a flame-failure safety device that cuts the gas supply if a flame goes out. Heavy cast-iron pan supports and both LPG/NG compatibility make it a robust everyday cooking surface.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '3 brass burners',
                    'Golden toughened tempered-glass top',
                    'Auto electric ignition',
                    'Flame-failure safety device',
                    'Heavy cast-iron pan supports',
                    'LPG & Natural Gas compatible',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Built-In Gas Hob', 'Colour' => 'Golden', 'Top Material' => 'Toughened Tempered Glass', 'Model' => 'GL 308 DG BR'],
                    'Capacity & Performance' => ['Number of Burners' => '3 (Brass)', 'Ignition' => 'Auto Electric Ignition', 'Safety' => 'Flame Failure Device (FFD)', 'Gas Type' => 'LPG / Natural Gas'],
                    'Power' => ['Ignition Supply' => 'Electric (220-240V)'],
                    'Physical' => ['Pan Support' => 'Cast Iron', 'Dimensions (approx.)' => '760 x 430 mm'],
                ],
            ],
            [
                'name' => 'Ideal Appliances Premium 3-Burner Built-In Stainless Steel Gas Hob',
                'category' => 'Built-In Hobs',
                'brand' => 'Ideal',
                'retail' => 28000,
                'compare' => 33000,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Durable 3-burner built-in hob with a stainless steel top, brass burners and auto-ignition for daily cooking.',
                'description' => 'The Ideal Appliances 3-burner built-in gas hob features a rust-resistant stainless steel top that is easy to clean and built to last. It combines three brass burners with auto electric ignition and a flame-failure safety device for peace of mind. Heavy-duty pan supports and dual LPG/NG compatibility make it a dependable choice for the modern Pakistani kitchen.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '3 brass burners',
                    'Rust-resistant stainless steel top',
                    'Auto electric ignition',
                    'Flame-failure safety device',
                    'Heavy-duty pan supports',
                    'LPG & Natural Gas compatible',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Built-In Gas Hob', 'Colour' => 'Silver / Stainless Steel', 'Top Material' => 'Stainless Steel'],
                    'Capacity & Performance' => ['Number of Burners' => '3 (Brass)', 'Ignition' => 'Auto Electric Ignition', 'Safety' => 'Flame Failure Device (FFD)', 'Gas Type' => 'LPG / Natural Gas'],
                    'Power' => ['Ignition Supply' => 'Electric (220-240V)'],
                    'Physical' => ['Pan Support' => 'Cast Iron', 'Dimensions (approx.)' => '760 x 430 mm'],
                ],
            ],
            [
                'name' => 'Hybrid Gas & Electric Built-In Kitchen Hob',
                'category' => 'Built-In Hobs',
                'brand' => null,
                'retail' => 52000,
                'compare' => 60000,
                'featured' => false, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Dual-energy built-in hob pairing two brass gas burners with an electric ceramic plate so you can cook with or without gas.',
                'description' => 'This hybrid built-in kitchen hob combines two high-power brass gas burners with an electric ceramic hotplate, letting you keep cooking even during gas load-shedding. The 90cm tempered ceramic-glass top provides a spacious, easy-to-clean surface, while auto-ignition and a flame-failure device ensure safe operation. It is the ideal all-weather cooking solution for homes facing an uncertain gas supply.',
                'warranty' => '1 Year Warranty',
                'highlights' => [
                    '2 brass gas burners + 1 electric ceramic plate',
                    'Cook with or without gas supply',
                    '90cm tempered ceramic-glass top',
                    'Auto electric ignition',
                    'Flame-failure safety device',
                    'LPG & Natural Gas compatible',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Hybrid Built-In Hob (Gas + Electric)', 'Colour' => 'Black', 'Top Material' => 'Tempered Ceramic Glass'],
                    'Capacity & Performance' => ['Gas Burners' => '2 (Brass, ~4.2 kW each)', 'Electric Plate' => '1 Ceramic Hotplate (2000 W)', 'Ignition' => 'Auto Electric Ignition', 'Safety' => 'Flame Failure Device (FFD)', 'Gas Type' => 'LPG / Natural Gas'],
                    'Power' => ['Electric Plate Power' => '2000 W', 'Voltage / Frequency' => '220-240V, 50Hz'],
                    'Physical' => ['Width' => '90 cm', 'Dimensions (approx.)' => '900 x 520 mm'],
                ],
            ],
            [
                'name' => 'Choice Appliances Single-Burner Built-In Gas Hob',
                'category' => 'Built-In Hobs',
                'brand' => 'Choice',
                'retail' => 12500,
                'compare' => 15000,
                'featured' => false, 'trending' => false, 'bestseller' => false,
                'short_description' => 'Compact single-burner built-in glass hob with brass burner and auto-ignition, ideal for small kitchens.',
                'description' => 'The Choice Appliances single-burner built-in gas hob is a compact cooking solution for small kitchens, apartments or as a supplementary burner. It features a durable brass burner set into a toughened tempered-glass top with auto electric ignition for easy lighting. A heavy pan support and dual LPG/NG compatibility round out a neat, space-saving design.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    'Single brass burner',
                    'Toughened tempered-glass top',
                    'Auto electric ignition',
                    'Heavy-duty pan support',
                    'Space-saving compact design',
                    'LPG & Natural Gas compatible',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Built-In Gas Hob', 'Colour' => 'Black', 'Top Material' => 'Toughened Tempered Glass'],
                    'Capacity & Performance' => ['Number of Burners' => '1 (Brass)', 'Ignition' => 'Auto Electric Ignition', 'Gas Type' => 'LPG / Natural Gas'],
                    'Power' => ['Ignition Supply' => 'Electric (220-240V)'],
                    'Physical' => ['Pan Support' => 'Enamel / Cast Iron', 'Dimensions (approx.)' => '300 x 500 mm'],
                ],
            ],

            // ---- Range hoods, cooking ranges, cooktop & instant geyser -------
            [
                'name' => 'Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)',
                'category' => 'Kitchen Hoods',
                'brand' => 'Glam Gas',
                'retail' => 44900,
                'compare' => 52000,
                'featured' => true, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Smart auto-clean kitchen chimney with hand-gesture control and powerful 1400 m3/hr suction built for heavy Pakistani cooking.',
                'description' => 'The Glam Gas Wave Series is a wall-mounted electric kitchen range hood engineered for high-oil Pakistani cooking, pairing a pure-copper motor with a one-touch auto-clean system that clears grease build-up in seconds. Touch-free hand-gesture control, a smart touch panel, LED task lighting and a large oil collector make daily use effortless. Removable stainless-steel baffle filters keep maintenance to a monthly wipe-down.',
                'warranty' => '1-year warranty on motor, blower and PCB; 2-year warranty on touch panel',
                'highlights' => [
                    'Powerful suction up to 1400 m3/hr',
                    'One-touch auto-clean system',
                    'Hand-gesture, touch-free control',
                    'Pure copper motor with double ball bearings',
                    'Bright LED lighting for the cooktop',
                    'Large 16-inch oil collector for high-oil cooking',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Wall-mounted electric range hood (chimney)', 'Control' => 'Hand-gesture + smart touch panel', 'Auto Clean' => 'Yes, one-touch', 'Filter' => 'Removable stainless-steel baffle filter', 'Lighting' => 'LED'],
                    'Capacity & Performance' => ['Suction Power' => '1400 m3/hr', 'Blower Speeds' => '3 speeds', 'Noise Level' => '58 dB'],
                    'Power' => ['Motor' => 'Pure copper winding with double ball bearings', 'Power Consumption' => '220 W', 'Voltage' => '220-240V / 50Hz'],
                    'Physical' => ['Width' => '90 cm', 'Body Material' => 'Stainless steel', 'Colour' => 'Black'],
                ],
            ],
            [
                'name' => 'Choice Appliances Premium Curved Glass Kitchen Range Hood (Chimney)',
                'category' => 'Kitchen Hoods',
                'brand' => 'Choice',
                'retail' => 33500,
                'compare' => 39900,
                'featured' => false, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Elegant curved tempered-glass chimney with 1100 m3/hr suction, push-button control and easy-clean baffle filters.',
                'description' => 'The Choice Premium Curved Glass range hood blends a sleek curved tempered-glass canopy with dependable ventilation for the modern Pakistani kitchen. Its high-efficiency motor delivers strong suction to clear smoke, steam and cooking odours, while stainless-steel baffle filters trap grease and lift out for easy cleaning. Twin LED lamps illuminate the hob and simple push-button controls manage speed and lighting.',
                'warranty' => '1-year warranty on motor and parts',
                'highlights' => [
                    'Curved tempered-glass canopy design',
                    'Suction power around 1100 m3/hr',
                    'Stainless-steel baffle filters',
                    'Twin LED cooktop lighting',
                    'Simple push-button controls',
                    'Wall-mounted, fits 60-90 cm hobs',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Wall-mounted curved glass range hood (chimney)', 'Control' => 'Push-button', 'Filter' => 'Stainless-steel baffle filter', 'Lighting' => '2x LED'],
                    'Capacity & Performance' => ['Suction Power' => '1100 m3/hr', 'Blower Speeds' => '3 speeds', 'Noise Level' => '60 dB'],
                    'Power' => ['Power Consumption' => '190 W', 'Voltage' => '220-240V / 50Hz'],
                    'Physical' => ['Width' => '90 cm', 'Canopy Material' => 'Curved tempered glass with stainless-steel body', 'Colour' => 'Black glass'],
                ],
            ],
            [
                'name' => 'National Free-Standing 3-Burner Cooking Range with Oven & Grill',
                'category' => 'Cooking Ranges',
                'brand' => 'National',
                'retail' => 46900,
                'compare' => 54900,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Compact free-standing 3-burner gas cooking range with a full-size gas oven, grill and auto-ignition, ideal for small families.',
                'description' => 'This National free-standing cooking range packs three efficient gas burners over a spacious gas oven with an integrated grill, making it a practical all-in-one kitchen workhorse for small to medium families. Auto-ignition lights every burner instantly, while the glass oven door and internal light let you monitor baking and roasting at a glance. A stainless-steel top with a durable enamel body keeps cleaning simple.',
                'warranty' => '1-year warranty on burners and oven parts',
                'highlights' => [
                    'Three gas burners with auto-ignition',
                    'Full-size gas oven with grill function',
                    'Glass oven door with internal light',
                    'Stainless-steel cooktop',
                    'Enamelled pan supports and drip trays',
                    'Free-standing design for easy installation',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Free-standing gas cooking range', 'Burners' => '3 gas burners', 'Oven' => 'Gas oven with grill', 'Ignition' => 'Auto-ignition'],
                    'Capacity & Performance' => ['Oven Type' => 'Gas oven with grill and rotisserie', 'Oven Door' => 'Glass with internal light', 'Cooktop' => 'Stainless steel'],
                    'Power' => ['Gas Type' => 'Natural gas / LPG compatible', 'Ignition Power' => 'Electric auto-ignition (battery/electric)'],
                    'Physical' => ['Dimensions' => '50 x 55 x 85 cm', 'Body Material' => 'Powder-coated steel with stainless-steel top', 'Colour' => 'Black & Silver'],
                ],
            ],
            [
                'name' => 'National Premium 5-Burner Double-Door Cooking Range with Oven & Grill',
                'category' => 'Cooking Ranges',
                'brand' => 'National',
                'retail' => 72900,
                'compare' => 84900,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Spacious 5-burner free-standing cooking range with a large double-door glass oven, grill and full stainless-steel body.',
                'description' => 'The National Premium 5-burner cooking range is built for large families and serious home cooks, offering five powerful gas burners and a generous double-door glass oven with grill and rotisserie. The stainless-steel body resists heat and stains, while auto-ignition and an internal oven light add everyday convenience. Twin oven doors give easy access to the roasting and baking chambers for big meals.',
                'warranty' => '1-year warranty on burners and oven parts',
                'highlights' => [
                    'Five high-efficiency gas burners',
                    'Double-door glass oven with grill',
                    'Full stainless-steel body',
                    'Auto-ignition on all burners',
                    'Rotisserie and internal oven light',
                    'Spacious capacity for large families',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Free-standing gas cooking range', 'Burners' => '5 gas burners', 'Oven' => 'Double-door gas oven with grill', 'Ignition' => 'Auto-ignition'],
                    'Capacity & Performance' => ['Oven Type' => 'Gas oven with grill and rotisserie', 'Oven Door' => 'Double glass door with internal light', 'Cooktop' => 'Stainless steel'],
                    'Power' => ['Gas Type' => 'Natural gas / LPG compatible', 'Ignition Power' => 'Electric auto-ignition'],
                    'Physical' => ['Dimensions' => '90 x 60 x 90 cm', 'Body Material' => 'Stainless steel', 'Colour' => 'Stainless Steel / Silver'],
                ],
            ],
            [
                'name' => 'RAF Multifunction Infrared Cooker & Hot Plate R.8045 (3500W)',
                'category' => 'Cooktops',
                'brand' => 'RAF',
                'retail' => 6349,
                'compare' => 8999,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Portable 3500W infrared cooker and hot plate with adjustable burner control, safe for all cookware and flame-free cooking.',
                'description' => 'The RAF R.8045 is a portable multifunction infrared cooktop that uses infrared heat-wave technology to cook without flame or smoke, delivering fast, even heat for everyday use. A high 3500W output with adjustable burner/temperature control handles frying, boiling and simmering, and unlike induction it works with any pot or pan including steel, glass and clay. Its compact micro-crystal plate wipes clean in seconds and suits small kitchens, hostels and offices.',
                'warranty' => '6-month warranty',
                'highlights' => [
                    'High 3500W infrared heating',
                    'Adjustable burner/temperature control',
                    'Flame-free, smoke-free cooking',
                    'Works with any cookware (not induction-limited)',
                    'Easy-clean micro-crystal glass plate',
                    'Compact and portable',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Portable infrared electric cooker / hot plate', 'Burners' => 'Single', 'Plate Material' => 'Micro-crystal tempered glass', 'Control' => 'Rotary burner / temperature knob'],
                    'Capacity & Performance' => ['Heating Technology' => 'Infrared heat-wave', 'Cookware Compatibility' => 'All cookware types', 'Heat Settings' => 'Variable'],
                    'Power' => ['Wattage' => '3500 W', 'Voltage' => '220-240V / 50Hz'],
                    'Physical' => ['Dimensions' => '30 x 34 x 7 cm', 'Colour' => 'Black'],
                ],
            ],
            [
                'name' => 'Glam Gas Instant Gas Water Heater (Tankless Geyser)',
                'category' => 'Instant Geysers',
                'brand' => 'Glam Gas',
                'retail' => 24900,
                'compare' => 29900,
                'featured' => false, 'trending' => false, 'bestseller' => false,
                'short_description' => 'Tankless instant gas geyser delivering endless hot water on demand with battery ignition and multiple safety protections.',
                'description' => 'The Glam Gas instant gas water heater is a compact tankless geyser that heats water the moment you open the tap, so there is no waiting and no storage tank to reheat. Battery-powered ignition fires the burner automatically on water flow, while flame-failure, anti-freeze and overheat protections keep operation safe. Its copper heat exchanger and adjustable water and gas controls deliver a steady flow of hot water for kitchens and bathrooms.',
                'warranty' => '1-year warranty on parts; 5-year warranty on heat exchanger',
                'highlights' => [
                    'Instant on-demand hot water, no storage tank',
                    'Automatic battery ignition on water flow',
                    'Copper heat exchanger for fast heating',
                    'Flame-failure and overheat safety protection',
                    'Adjustable water and gas flow controls',
                    'Compact wall-mounted design',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Tankless instant gas water heater (geyser)', 'Ignition' => 'Battery-powered auto ignition', 'Installation' => 'Wall-mounted', 'Safety' => 'Flame-failure, anti-freeze and overheat protection'],
                    'Capacity & Performance' => ['Flow Rate' => '10 litres/min', 'Heat Exchanger' => 'Copper', 'Temperature Rise' => 'Adjustable'],
                    'Power' => ['Gas Type' => 'Natural gas / LPG', 'Ignition Power' => '2x D-size batteries', 'Max Gas Consumption' => '20 kW'],
                    'Physical' => ['Dimensions' => '34 x 62 x 20 cm', 'Body Material' => 'Powder-coated steel', 'Colour' => 'White'],
                ],
            ],

            // ---- Fans, cooler & solar panel ----------------------------------
            [
                'name' => 'Sogo 18-Inch 12V DC Solar Pedestal Fan (Rechargeable, AC/DC/Solar)',
                'category' => 'Solar Fans',
                'brand' => 'Sogo',
                'retail' => 11999,
                'compare' => 14500,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => '18-inch 12V DC pedestal fan that runs on mains, a 12V battery or a solar panel with a pure-copper energy-saving motor.',
                'description' => 'This 18-inch DC pedestal fan is built for load-shedding and off-grid use, running directly on 220V AC via the included adapter or on 12V DC from a battery or solar panel. Its pure-copper BLDC-style motor draws only about 36W while delivering strong airflow at up to 1600 RPM across three speeds. The height-adjustable stand, wide oscillation and metal grille make it suitable for homes, shops and offices during power outages.',
                'warranty' => '1 year brand warranty (motor)',
                'highlights' => [
                    'Runs on 220V AC, 12V battery or solar panel',
                    'Low 36W power draw on pure-copper motor',
                    '3 speeds with wide oscillation',
                    'Free AC/DC adapter and battery wire included',
                    'Height-adjustable stand, 18-inch metal blades',
                    'Ideal for load-shedding and off-grid use',
                ],
                'specifications' => [
                    'General' => ['Type' => 'DC Pedestal / Stand Fan', 'Model' => 'Solar 12V DC 18-inch', 'Colour' => 'White / Grey', 'Material' => 'ABS body with powder-coated metal grille'],
                    'Capacity & Performance' => ['Sweep Size' => '18 inch (450 mm)', 'Speed' => 'Up to 1600 RPM', 'Speed Settings' => '3 (Low / Medium / High)', 'Oscillation' => 'Yes, wide angle'],
                    'Power' => ['Operating Voltage' => '12V DC / 220V AC', 'Power Consumption' => '36 W', 'Solar Panel Support' => '40W+ 12V panel', 'Battery Support' => '12V DC battery', 'Included' => 'AC/DC adapter, battery clip wire'],
                    'Physical' => ['Height' => 'Adjustable, approx. 1.3 m', 'Net Weight' => 'Approx. 5 kg'],
                ],
            ],
            [
                'name' => 'Global High Chill Tower Air Cooler with Ice Box & Remote',
                'category' => 'Air Cooler',
                'brand' => 'Global',
                'retail' => 34999,
                'compare' => 42000,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Tall tower air cooler with a large water tank, honeycomb pads, ice box and full-function remote for powerful whole-room cooling.',
                'description' => 'The Global High Chill tower air cooler is designed for large rooms, combining a slim tower footprint with a high-capacity water tank for long, refill-free cooling sessions. High-density honeycomb cooling pads and a dedicated ice box deliver noticeably colder air even on 45 degree afternoons, while a full-function remote controls speed, oscillation and timer from across the room. Castor wheels make it easy to move between rooms as needed.',
                'warranty' => '1 year brand warranty',
                'highlights' => [
                    'Large-capacity water tank for long cooling cycles',
                    'Honeycomb cooling pads for cooler, cleaner air',
                    'Dedicated ice box for extra chill',
                    'Full-function remote control with timer',
                    '3-speed motor with wide oscillation',
                    'Castor wheels for easy movement',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Tower / Room Air Cooler', 'Model' => 'High Chill', 'Colour' => 'White with grey accents', 'Cooling Media' => 'Honeycomb pads'],
                    'Capacity & Performance' => ['Water Tank Capacity' => '50 L', 'Air Throw' => 'Up to 30 ft', 'Speed Settings' => '3 (Low / Medium / High)', 'Oscillation' => 'Yes', 'Ice Box' => 'Yes'],
                    'Power' => ['Operating Voltage' => '220-240V AC, 50Hz', 'Power Consumption' => '180 W', 'Remote Control' => 'Yes'],
                    'Physical' => ['Dimensions' => 'Approx. 34 x 34 x 120 cm', 'Net Weight' => 'Approx. 12 kg', 'Mobility' => '4 castor wheels'],
                ],
            ],
            [
                'name' => 'Longi Hi-MO 550W Monocrystalline Mono PERC Solar Panel',
                'category' => 'Solar Plates',
                'brand' => 'Longi',
                'retail' => 20999,
                'compare' => 26500,
                'featured' => true, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Tier-1 550W monocrystalline mono PERC half-cut solar panel with high efficiency and 12-year product warranty.',
                'description' => 'The Longi Hi-MO 550W is a Tier-1 monocrystalline PERC solar panel widely used in Pakistani residential and commercial solar systems. Its half-cut cell design improves shade tolerance and lowers resistive losses, delivering around 21% module efficiency and strong low-light performance. Built with an anodised aluminium frame and tempered glass, it is engineered to withstand high wind and snow loads across Pakistan\'s climate.',
                'warranty' => '12 years product warranty, 25 years performance warranty',
                'highlights' => [
                    '550W peak output, Tier-1 grade',
                    'Monocrystalline PERC half-cut cells',
                    '~21% module efficiency',
                    'Anti-reflective tempered glass, anodised aluminium frame',
                    'High wind and snow load resistance',
                    '25-year linear performance warranty',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Monocrystalline PV Module', 'Series' => 'Hi-MO', 'Cell Type' => 'Mono PERC Half-Cut', 'Number of Cells' => '144 (6x24)'],
                    'Capacity & Performance' => ['Rated Power (Pmax)' => '550 W', 'Module Efficiency' => 'Approx. 21.3%', 'Max Power Voltage (Vmp)' => '41.9 V', 'Max Power Current (Imp)' => '13.13 A', 'Open Circuit Voltage (Voc)' => '49.9 V', 'Short Circuit Current (Isc)' => '13.9 A'],
                    'Power' => ['System Voltage' => '1500V DC', 'Operating Temperature' => '-40C to +85C'],
                    'Physical' => ['Dimensions' => 'Approx. 2278 x 1134 x 35 mm', 'Net Weight' => 'Approx. 27.2 kg', 'Frame' => 'Anodised aluminium alloy', 'Front Glass' => '3.2 mm tempered anti-reflective glass'],
                ],
            ],
            [
                'name' => 'GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)',
                'category' => 'DC Fans',
                'brand' => 'GFC',
                'retail' => 13999,
                'compare' => 16500,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => '56-inch AC/DC hybrid inverter ceiling fan running on mains, UPS or 12V solar/battery with a 99.99% pure-copper motor and remote.',
                'description' => 'The GFC 56-inch AC/DC hybrid inverter ceiling fan switches seamlessly between 220V AC mains and 12V DC from solar panels, batteries or a UPS, making it ideal for load-shedding and off-grid rooms. Its energy-saving BLDC inverter motor with 99.99% pure-copper winding consumes as little as 30-40W while maintaining strong air delivery at up to 370 RPM. A remote control handles speed, timer and turbo mode, and the aluminium-alloy blades ensure quiet, balanced operation.',
                'warranty' => '1 year circuit/PCB warranty, 3 years motor warranty',
                'highlights' => [
                    'Runs on 220V AC or 12V DC (solar/battery/UPS)',
                    'Energy-saving BLDC inverter motor, 30-40W',
                    '99.99% pure-copper winding',
                    'Remote control with speed, timer and turbo',
                    'Up to 370 RPM air delivery',
                    '56-inch aluminium-alloy blades, quiet operation',
                ],
                'specifications' => [
                    'General' => ['Type' => 'AC/DC Hybrid Inverter Ceiling Fan', 'Model' => 'Deluxe 56-inch', 'Colour' => 'White', 'Blade Material' => 'Aluminium alloy'],
                    'Capacity & Performance' => ['Sweep Size' => '56 inch (1400 mm)', 'Speed' => 'Up to 370 RPM (turbo)', 'Speed Settings' => 'Multi-speed via remote', 'Air Delivery' => 'High'],
                    'Power' => ['Operating Voltage' => '220V AC / 12V DC', 'Power Consumption' => '30-40 W', 'Solar/UPS Ready' => 'Yes', 'Remote Control' => 'Yes', 'Winding' => '99.99% pure copper'],
                    'Physical' => ['Down Rod' => 'Standard steel down rod', 'Net Weight' => 'Approx. 6 kg'],
                ],
            ],

            // ---- Gas stoves, patio heaters, regulator & blenders -------------
            [
                'name' => 'Shanghai Superior Quality 2-Burner Stainless Steel Tabletop Gas Stove',
                'category' => 'Stoves',
                'brand' => 'Shanghai',
                'retail' => 5999,
                'compare' => 7500,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Durable 2-burner stainless steel tabletop cooktop with brass burners and auto ignition for everyday Pakistani kitchens.',
                'description' => 'The Shanghai 2-Burner Stainless Steel Tabletop Gas Stove is a compact countertop cooker built on a rust-resistant stainless steel body with two high-efficiency brass burner heads. Auto (piezo) ignition lights the flame instantly without matches, while the enamelled cast-iron pan supports hold heavy pots and woks steadily. It runs on LPG cylinders or Sui gas and is sized to fit small kitchens, apartments and hostels.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    'Two high-efficiency brass burners',
                    'Rust-resistant stainless steel top',
                    'Instant auto piezo ignition',
                    'Heavy cast-iron pan supports',
                    'Works with LPG and Sui gas',
                    'Compact tabletop design',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Shanghai', 'Type' => 'Tabletop Gas Stove', 'Number of Burners' => '2', 'Ignition' => 'Auto (Piezo)', 'Gas Type' => 'LPG / Natural (Sui) Gas', 'Colour' => 'Silver / Stainless Steel'],
                    'Capacity & Performance' => ['Burner Material' => 'Brass', 'Heat Output (per burner)' => 'Approx. 3.2 kW (~11,000 BTU)', 'Pan Support' => 'Cast Iron', 'Flame Control' => 'Individual rotary knobs'],
                    'Physical' => ['Body Material' => 'Stainless Steel', 'Approx. Dimensions' => '600 x 340 x 130 mm', 'Approx. Weight' => '4.2 kg'],
                ],
            ],
            [
                'name' => 'Shanghai Single-Burner Stainless Steel Tabletop Gas Stove',
                'category' => 'Stoves',
                'brand' => 'Shanghai',
                'retail' => 2499,
                'compare' => 3200,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Compact single-burner stainless steel tabletop stove ideal for hostels, apartments and small households.',
                'description' => 'The Shanghai Single-Burner Tabletop Gas Stove packs a single high-flame brass burner into a slim stainless steel body that fits the tightest counters. It uses simple, reliable piezo ignition and a sturdy cast-iron pan support for stable cooking. Lightweight and easy to clean, it is a popular budget choice for students, bachelors and small families running on LPG or Sui gas.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    'Single high-flame brass burner',
                    'Slim stainless steel body',
                    'Piezo auto ignition',
                    'Cast-iron pan support',
                    'Lightweight and portable',
                    'LPG and Sui gas compatible',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Shanghai', 'Type' => 'Tabletop Gas Stove', 'Number of Burners' => '1', 'Ignition' => 'Auto (Piezo)', 'Gas Type' => 'LPG / Natural (Sui) Gas', 'Colour' => 'Silver / Stainless Steel'],
                    'Capacity & Performance' => ['Burner Material' => 'Brass', 'Heat Output' => 'Approx. 3.2 kW (~11,000 BTU)', 'Pan Support' => 'Cast Iron', 'Flame Control' => 'Single rotary knob'],
                    'Physical' => ['Body Material' => 'Stainless Steel', 'Approx. Dimensions' => '320 x 300 x 120 mm', 'Approx. Weight' => '2.3 kg'],
                ],
            ],
            [
                'name' => 'Super Fire Gas 2-Burner Stainless Steel Tabletop Gas Stove',
                'category' => 'Stoves',
                'brand' => 'Super Fire Gas',
                'retail' => 4999,
                'compare' => 6500,
                'featured' => false, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Heavy-duty 2-burner stainless steel tabletop stove with strong blue-flame brass burners and auto ignition.',
                'description' => 'The Super Fire Gas 2-Burner Stainless Steel Tabletop Gas Stove offers two powerful brass burners on a thick, easy-to-clean stainless steel panel. Auto ignition and precise rotary knobs give reliable, controllable blue flames for fast cooking, while heavy cast-iron trivets keep large pots and pressure cookers stable. It is designed for daily family cooking on both LPG and Sui gas.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    'Two powerful brass blue-flame burners',
                    'Thick stainless steel panel',
                    'Auto piezo ignition',
                    'Heavy cast-iron trivets',
                    'Precise flame control knobs',
                    'Runs on LPG and Sui gas',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Super Fire Gas', 'Type' => 'Tabletop Gas Stove', 'Number of Burners' => '2', 'Ignition' => 'Auto (Piezo)', 'Gas Type' => 'LPG / Natural (Sui) Gas', 'Colour' => 'Silver / Stainless Steel'],
                    'Capacity & Performance' => ['Burner Material' => 'Brass', 'Heat Output (per burner)' => 'Approx. 3.4 kW (~11,600 BTU)', 'Pan Support' => 'Cast Iron', 'Flame Control' => 'Individual rotary knobs'],
                    'Physical' => ['Body Material' => 'Stainless Steel', 'Approx. Dimensions' => '620 x 350 x 135 mm', 'Approx. Weight' => '4.5 kg'],
                ],
            ],
            [
                'name' => 'Umbrella-Style Stainless Steel Tabletop Mini Gas Patio Heater',
                'category' => 'Patio Heaters',
                'brand' => null,
                'retail' => 12500,
                'compare' => 16000,
                'featured' => false, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Compact tabletop umbrella-style gas patio heater that runs off a small LPG canister for cosy outdoor warmth.',
                'description' => 'This mini umbrella-style tabletop patio heater brings mushroom-heater warmth to a portable, table-friendly size. Its stainless steel reflector spreads radiant heat evenly around a table, and it operates from a small screw-on LPG canister housed in the base. Piezo ignition and a variable heat knob make it easy to light and control for balconies, rooftops and small outdoor gatherings.',
                'warranty' => '6 Months Warranty',
                'highlights' => [
                    'Compact tabletop umbrella design',
                    'Stainless steel radiant reflector',
                    'Runs on small LPG canister',
                    'Piezo ignition with variable control',
                    'Anti-tilt flame-out safety cut-off',
                    'Portable for balconies and rooftops',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Tabletop Gas Patio Heater', 'Style' => 'Umbrella / Mushroom', 'Fuel' => 'LPG (small canister)', 'Ignition' => 'Piezo', 'Colour' => 'Stainless Steel'],
                    'Capacity & Performance' => ['Heat Output' => 'Approx. 3 kW (~10,000 BTU)', 'Heat Control' => 'Variable knob', 'Safety' => 'Anti-tilt flame-out protection'],
                    'Physical' => ['Body Material' => 'Stainless Steel', 'Approx. Height' => '830 mm', 'Approx. Weight' => '3.5 kg'],
                ],
            ],
            [
                'name' => 'Full-Size Mushroom/Umbrella Style Outdoor Gas Patio Heater',
                'category' => 'Patio Heaters',
                'brand' => null,
                'retail' => 32500,
                'compare' => 39999,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Tall free-standing mushroom-style propane patio heater delivering up to ~13kW of radiant outdoor warmth.',
                'description' => 'This full-size mushroom/umbrella patio heater is a floor-standing propane heater that radiates heat in a wide circle for lawns, gardens, cafes and event spaces. A stainless steel burner and reflector hood push out up to around 13 kW of warmth, keeping a 4-5 metre area comfortable. It features a weighted base for stability, a variable heat control, piezo ignition and anti-tilt with flame-failure safety cut-offs; a full LPG cylinder sits inside the base column.',
                'warranty' => '1 Year Warranty',
                'highlights' => [
                    'Up to ~13 kW radiant heat output',
                    'Wide mushroom reflector hood',
                    'Free-standing weighted base',
                    'Piezo ignition, variable heat control',
                    'Anti-tilt and flame-failure safety',
                    'Houses a standard LPG cylinder',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Outdoor Gas Patio Heater', 'Style' => 'Mushroom / Umbrella (Floor-standing)', 'Fuel' => 'LPG / Propane', 'Ignition' => 'Piezo', 'Colour' => 'Stainless Steel / Bronze'],
                    'Capacity & Performance' => ['Heat Output' => 'Up to 13 kW (~46,000 BTU)', 'Coverage Area' => 'Approx. 4-5 m radius', 'Heat Control' => 'Variable knob', 'Safety' => 'Anti-tilt + flame-failure cut-off'],
                    'Physical' => ['Body Material' => 'Stainless Steel', 'Approx. Height' => '2210 mm', 'Reflector Diameter' => 'Approx. 810 mm', 'Approx. Weight' => '15 kg'],
                ],
            ],
            [
                'name' => 'Pyramid-Style Glass Tube Outdoor Gas Patio Heater',
                'category' => 'Patio Heaters',
                'brand' => null,
                'retail' => 65000,
                'compare' => 79999,
                'featured' => true, 'trending' => true, 'bestseller' => false,
                'short_description' => 'Premium pyramid patio heater with a mesmerising dancing flame inside a quartz glass tube and ~13kW output.',
                'description' => 'The Pyramid-Style Glass Tube Outdoor Gas Patio Heater combines striking looks with serious warmth, showcasing a dancing flame that rises through a tall quartz glass tube. Built from powder-coated stainless steel, it delivers around 13 kW of radiant heat, making it a favourite for upscale patios, restaurants and outdoor lounges. Wheels allow easy repositioning, and it includes piezo ignition, variable heat control, and anti-tilt with flame-failure safety systems, with the LPG cylinder concealed in the base.',
                'warranty' => '1 Year Warranty',
                'highlights' => [
                    'Dancing flame in a quartz glass tube',
                    'Powder-coated stainless steel frame',
                    'Up to ~13 kW radiant heat',
                    'Built-in wheels for mobility',
                    'Piezo ignition with variable control',
                    'Anti-tilt and flame-failure safety',
                ],
                'specifications' => [
                    'General' => ['Type' => 'Outdoor Gas Patio Heater', 'Style' => 'Pyramid / Glass Tube (Floor-standing)', 'Fuel' => 'LPG / Propane', 'Ignition' => 'Piezo', 'Colour' => 'Black / Stainless Steel'],
                    'Capacity & Performance' => ['Heat Output' => 'Approx. 13 kW (~46,000 BTU)', 'Coverage Area' => 'Approx. 4-5 m radius', 'Heat Control' => 'Variable knob', 'Safety' => 'Anti-tilt + flame-failure cut-off'],
                    'Physical' => ['Body Material' => 'Powder-coated Stainless Steel + Quartz Glass Tube', 'Approx. Height' => '2210 mm', 'Mobility' => 'Wheeled base', 'Approx. Weight' => '27 kg'],
                ],
            ],
            [
                'name' => 'Super Gree Clip-On Low-Pressure Gas Regulator',
                'category' => 'Gas Regulators',
                'brand' => 'Super Gree',
                'retail' => 850,
                'compare' => null,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Clip-on low-pressure LPG cylinder regulator delivering safe, steady gas flow to stoves and heaters.',
                'description' => 'The Super Gree Clip-On Low-Pressure Gas Regulator snaps securely onto a standard LPG cylinder valve to deliver a steady, low-pressure gas supply for household stoves, ovens and heaters. Its zinc-alloy body and internal safety diaphragm maintain a constant outlet pressure for a stable flame. The push-fit clip-on design fits common domestic cylinder valves and is quick to attach and remove.',
                'warranty' => 'No warranty',
                'highlights' => [
                    'Clip-on push-fit design',
                    'Steady low-pressure output',
                    'Durable zinc-alloy body',
                    'Internal safety diaphragm',
                    'Fits standard LPG cylinders',
                    'Easy to attach and remove',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Super Gree', 'Type' => 'Clip-On Low-Pressure LPG Regulator', 'Fuel' => 'LPG', 'Colour' => 'Silver / Grey'],
                    'Capacity & Performance' => ['Outlet Pressure' => 'Approx. 28-30 mbar', 'Flow Rate' => 'Approx. 1.5 kg/hr', 'Valve Fitting' => 'Clip-on (standard domestic cylinder)'],
                    'Physical' => ['Body Material' => 'Zinc Alloy', 'Approx. Weight' => '0.25 kg'],
                ],
            ],
            [
                'name' => 'National 3-in-1 Juicer, Blender and Dry Miller (Model MJ-176)',
                'category' => 'Blenders & Juicers',
                'brand' => 'National',
                'retail' => 6499,
                'compare' => 8999,
                'featured' => true, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Versatile 3-in-1 kitchen combo with juicer, blender jug and dry grinder mill powered by a strong copper motor.',
                'description' => 'The National 3-in-1 Juicer, Blender and Dry Miller (MJ-176) bundles three appliances into one compact unit: a fruit juicer, a large blending jug for shakes and purees, and a dry mill for grinding spices, coffee and dry masalas. A powerful copper-wound motor with stainless steel blades handles tough tasks, while multiple speed settings plus a pulse function give full control. Shatter-resistant jars and easy-lock lids make daily use and cleaning simple.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '3-in-1 juicer, blender and dry mill',
                    'Powerful copper-wound motor',
                    'Stainless steel blades',
                    'Multiple speeds with pulse',
                    'Shatter-resistant jars',
                    'Easy-lock lids for safe use',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'National', 'Model' => 'MJ-176', 'Type' => '3-in-1 Juicer / Blender / Dry Mill', 'Colour' => 'White'],
                    'Capacity & Performance' => ['Blender Jar Capacity' => '1.5 L', 'Speed Settings' => '2 speeds + Pulse', 'Blade Material' => 'Stainless Steel', 'Functions' => 'Juicing, Blending, Dry Grinding'],
                    'Power' => ['Wattage' => '1000 W', 'Motor' => 'Copper-wound', 'Voltage' => '220-240 V / 50 Hz'],
                    'Physical' => ['Body Material' => 'ABS Plastic', 'Jar Material' => 'Shatter-resistant Plastic', 'Approx. Weight' => '3.2 kg'],
                ],
            ],
            [
                'name' => 'Panasonic Classic 2-in-1 Blender and Dry Grinder Mill (Model HJ-661)',
                'category' => 'Blenders & Juicers',
                'brand' => 'Panasonic',
                'retail' => 5499,
                'compare' => 7000,
                'featured' => false, 'trending' => false, 'bestseller' => true,
                'short_description' => 'Reliable 2-in-1 blender and dry grinder mill for smoothies, chutneys and grinding spices and coffee.',
                'description' => 'The Panasonic Classic 2-in-1 Blender and Dry Grinder Mill (HJ-661) pairs a large blending jug with a compact dry-grinding mill so you can make juices, shakes and chutneys and grind spices or coffee with one machine. Stainless steel blades and a durable copper motor deliver smooth, consistent results, while multiple speeds and a pulse setting handle both soft and hard ingredients. The impact-resistant jars and simple twist-lock design keep everyday use quick and mess-free.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '2-in-1 blender and dry mill',
                    'Durable copper motor',
                    'Stainless steel blades',
                    'Multiple speeds with pulse',
                    'Impact-resistant jars',
                    'Twist-lock safety fitting',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Panasonic', 'Model' => 'HJ-661', 'Type' => '2-in-1 Blender / Dry Mill', 'Colour' => 'White'],
                    'Capacity & Performance' => ['Blender Jar Capacity' => '1.5 L', 'Mill Capacity' => '0.3 L', 'Speed Settings' => '2 speeds + Pulse', 'Blade Material' => 'Stainless Steel'],
                    'Power' => ['Wattage' => '500 W', 'Motor' => 'Copper-wound', 'Voltage' => '220-240 V / 50 Hz'],
                    'Physical' => ['Body Material' => 'ABS Plastic', 'Jar Material' => 'Impact-resistant Plastic', 'Approx. Weight' => '2.8 kg'],
                ],
            ],
            [
                'name' => 'Kenwood 2-in-1 Blender and Grinder Mill (Model KW-871)',
                'category' => 'Blenders & Juicers',
                'brand' => 'Kenwood',
                'retail' => 7999,
                'compare' => 10500,
                'featured' => false, 'trending' => true, 'bestseller' => true,
                'short_description' => 'Sturdy Kenwood 2-in-1 blender with a dry grinder mill for shakes, purees and grinding spices and coffee.',
                'description' => 'The Kenwood 2-in-1 Blender and Grinder Mill (KW-871) combines a large blending jug with a dedicated grinding mill for spices, coffee beans and dry masalas. Its strong motor and stainless steel blades crush ice and blend thick shakes smoothly, while variable speeds and a pulse function give precise texture control. Robust, easy-lock jars and a stable base make it a dependable everyday kitchen workhorse.',
                'warranty' => '1 Year Brand Warranty',
                'highlights' => [
                    '2-in-1 blender and grinder mill',
                    'Powerful motor for ice crushing',
                    'Stainless steel blades',
                    'Variable speeds with pulse',
                    'Robust easy-lock jars',
                    'Stable anti-slip base',
                ],
                'specifications' => [
                    'General' => ['Brand' => 'Kenwood', 'Model' => 'KW-871', 'Type' => '2-in-1 Blender / Grinder Mill', 'Colour' => 'White / Silver'],
                    'Capacity & Performance' => ['Blender Jar Capacity' => '1.6 L', 'Mill Capacity' => '0.3 L', 'Speed Settings' => '2 speeds + Pulse', 'Blade Material' => 'Stainless Steel'],
                    'Power' => ['Wattage' => '500 W', 'Motor' => 'Copper-wound', 'Voltage' => '220-240 V / 50 Hz'],
                    'Physical' => ['Body Material' => 'ABS Plastic', 'Jar Material' => 'Impact-resistant Plastic', 'Approx. Weight' => '3.0 kg'],
                ],
            ],
        ];
    }
}
