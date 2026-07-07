<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo blog content tied to the real catalogue (CatalogSeeder): appliance-focused
 * categories, a tag cloud, and published posts whose bodies deep-link to the actual
 * product and category pages. Idempotent (updateOrCreate by slug; pivots synced).
 *
 * Runs after CatalogSeeder so product links resolve to real slugs.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->orderBy('id')->first();
        if (! $author) {
            return; // needs the seeded admin as the post author
        }

        // Blog categories ---------------------------------------------------------
        $categories = [];
        foreach (['Buying Guides', 'Product Reviews', 'Tips & Maintenance', 'Energy Saving', 'News & Offers'] as $i => $name) {
            $categories[$name] = BlogCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i],
            );
        }

        // Tag cloud ---------------------------------------------------------------
        $tags = [];
        foreach ([
            'Air Cooler', 'Water Cooler', 'Washing Machine', 'Geyser', 'Solar', 'Fans', 'Stove',
            'Energy Saving', 'Summer', 'Winter', 'Maintenance', 'Buying Guide', 'Inverter',
            'Home Appliances', 'Load Shedding', 'Reviews',
        ] as $name) {
            $tags[$name] = BlogTag::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        // Link helpers — relative URLs so they work regardless of host/port. -------
        $link = function (string $productName) {
            $product = Product::where('name', $productName)->first();
            $url = $product ? route('product.show', $product->slug, false) : route('shop', [], false);

            return '<a href="' . $url . '">' . e($productName) . '</a>';
        };
        $catLink = fn (string $label, string $slug) => '<a href="' . route('shop', ['category' => $slug], false) . '">' . e($label) . '</a>';

        // Posts -------------------------------------------------------------------
        $posts = [
            [
                'title' => 'How to Choose the Right Room Air Cooler for a Pakistani Summer',
                'excerpt' => 'Tank size, air throw and honeycomb pads — everything that actually matters when picking an air cooler that survives a 45°C afternoon.',
                'categories' => ['Buying Guides'],
                'tags' => ['Air Cooler', 'Summer', 'Buying Guide'],
                'body' => '<p>When the mercury crosses 40°C across Punjab and Sindh, a good room air cooler is still the most affordable way to stay comfortable — especially through long load-shedding hours.</p>'
                    . '<h2>What actually matters</h2>'
                    . '<ul>'
                    . '<li><strong>Tank capacity</strong> — a bigger tank means fewer refills across a hot day.</li>'
                    . '<li><strong>Air throw</strong> — how far the cool air is pushed into the room.</li>'
                    . '<li><strong>Honeycomb pads &amp; ice box</strong> — for colder, cleaner air.</li>'
                    . '</ul>'
                    . '<h2>Our top picks</h2>'
                    . '<p>The ' . $link('Super Asia Room Air Cooler ECM-4000') . ' is a dependable all-rounder for a bedroom or lounge, while the ' . $link('Boss Room Air Cooler ECM-9000 Icy Cool') . ' moves more air for bigger halls.</p>'
                    . '<p>See the full range in our ' . $catLink('Air Coolers', 'air-cooler') . ' collection.</p>',
            ],
            [
                'title' => 'Instant vs Electric vs Gas Geysers: Which One Should You Buy?',
                'excerpt' => 'A plain-English comparison of the three geyser types so you pick the right one for your gas pressure, budget and family size.',
                'categories' => ['Buying Guides'],
                'tags' => ['Geyser', 'Winter', 'Buying Guide'],
                'body' => '<p>As winter sets in, hot water stops being a luxury. But which geyser fits your home? Here is the short version.</p>'
                    . '<h2>Instant gas geysers</h2>'
                    . '<p>Great for kitchens and small bathrooms where you want hot water on demand with no waiting. Try the ' . $link('Boss Instant Gas Geyser 6L') . '.</p>'
                    . '<h2>Electric storage geysers</h2>'
                    . '<p>Best where gas pressure is low. They store and hold heat — the ' . $link('PEL Electric Storage Geyser 30 Gallon') . ' is a solid family-sized option.</p>'
                    . '<h2>Gas storage geysers</h2>'
                    . '<p>The most economical to run where gas is reliable. The ' . $link('Super Asia Gas Geyser 35 Gallon') . ' keeps a large tank ready for the whole house.</p>'
                    . '<p>Compare every model in the ' . $catLink('Geysers', 'geysers') . ' section.</p>',
            ],
            [
                'title' => 'DC Inverter Fans: Beat Load-Shedding and Cut Your Electricity Bill',
                'excerpt' => 'A DC inverter ceiling fan runs on UPS/solar for far longer and uses a fraction of the power. Here is why it pays for itself.',
                'categories' => ['Energy Saving'],
                'tags' => ['Fans', 'Inverter', 'Energy Saving', 'Load Shedding'],
                'body' => '<p>A conventional ceiling fan draws around 80–100 watts. A modern DC inverter fan does the same job on roughly 30–35 watts — so it runs much longer on a UPS or solar battery.</p>'
                    . '<h2>Why switch</h2>'
                    . '<ul>'
                    . '<li>Runs 2–3× longer on backup power during load-shedding.</li>'
                    . '<li>Lower monthly units on your electricity bill.</li>'
                    . '<li>Quieter operation and remote control on most models.</li>'
                    . '</ul>'
                    . '<p>The ' . $link('PEL DC Inverter Ceiling Fan SmartSaver') . ' is our pick for backup-friendly cooling, while the classic ' . $link('GFC Ceiling Fan Deluxe 56 inch') . ' remains a great-value AC fan.</p>'
                    . '<p>Explore all models in the ' . $catLink('Fans', 'fans') . ' collection.</p>',
            ],
            [
                'title' => 'Going Solar: Is a 550W Solar Panel Worth It for Your Home?',
                'excerpt' => 'With rising tariffs, rooftop solar has never made more sense. We break down panel wattage, payback and where to start.',
                'categories' => ['Energy Saving'],
                'tags' => ['Solar', 'Energy Saving', 'Buying Guide'],
                'body' => '<p>Electricity tariffs keep climbing, and a rooftop solar system is now one of the smartest home investments you can make in Pakistan.</p>'
                    . '<h2>Start with the panels</h2>'
                    . '<p>High-efficiency mono PERC panels like the ' . $link('Homage Solar Panel 550W Mono PERC') . ' generate more power per square foot, so you need fewer panels for the same output.</p>'
                    . '<h2>Rough payback</h2>'
                    . '<p>Most homes recover their investment within 3–4 years, then enjoy years of near-free daytime power. Pair the panels with an inverter and batteries sized to your load.</p>'
                    . '<p>See the ' . $catLink('Solar Plates', 'solar-plates') . ' range to plan your system.</p>',
            ],
            [
                'title' => 'Automatic vs Twin-Tub Washing Machines: A Practical Comparison',
                'excerpt' => 'Fully automatic convenience or twin-tub value? We compare water use, wash quality and price to help you decide.',
                'categories' => ['Buying Guides', 'Product Reviews'],
                'tags' => ['Washing Machine', 'Buying Guide', 'Reviews'],
                'body' => '<p>The washing machine aisle really comes down to two choices: fully automatic or twin-tub. Each suits a different home.</p>'
                    . '<h2>Fully automatic</h2>'
                    . '<p>Load it, press start, walk away. The ' . $link('Dawlance Automatic Washing Machine DWT-260') . ' handles wash, rinse and spin in one cycle — ideal for busy families.</p>'
                    . '<h2>Twin-tub</h2>'
                    . '<p>More hands-on, but lighter on water and on the wallet. The ' . $link('Haier Twin Tub Washing Machine HWM-120') . ' is a proven, budget-friendly workhorse.</p>'
                    . '<p>Browse both styles in ' . $catLink('Washing Machines', 'washing-machine') . '.</p>',
            ],
            [
                'title' => 'Water Coolers &amp; Dispensers: Staying Hydrated Through the Heat',
                'excerpt' => 'From electric water coolers to 3-tap dispensers — how to keep chilled water flowing all summer at home or the office.',
                'categories' => ['Buying Guides'],
                'tags' => ['Water Cooler', 'Summer', 'Home Appliances'],
                'body' => '<p>Whether it is a busy office or a large family, having cold water on tap through the summer is a small comfort that makes a big difference.</p>'
                    . '<h2>Electric water coolers</h2>'
                    . '<p>For high demand, a stainless-steel electric cooler like the ' . $link('Waves Electric Water Cooler 65L') . ' chills large volumes fast.</p>'
                    . '<h2>Water dispensers</h2>'
                    . '<p>At home, a ' . $link('Orient 3-Tap Water Dispenser Icon') . ' gives you hot, cold and normal water from one neat unit.</p>'
                    . '<p>See more in ' . $catLink('Water Coolers', 'water-cooler') . ' and ' . $catLink('Water Dispensers', 'water-dispenser') . '.</p>',
            ],
            [
                'title' => '5 Simple Tips to Keep Your Home Appliances Running Longer',
                'excerpt' => 'A few minutes of care a month can add years to your appliances. Here are five easy habits that genuinely help.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Maintenance', 'Home Appliances', 'Stove'],
                'body' => '<p>Good appliances are an investment — treat them well and they will serve you for years. Five habits that make the biggest difference:</p>'
                    . '<ol>'
                    . '<li><strong>Clean the filters &amp; pads.</strong> Air-cooler honeycombs and washing-machine filters clog quickly.</li>'
                    . '<li><strong>Descale hot appliances.</strong> Geysers and kettles last longer without mineral build-up.</li>'
                    . '<li><strong>Wipe the burners.</strong> A quick clean keeps a stove like the ' . $link('Kenwood 5-Burner Gas Stove Crystal') . ' burning blue and efficient.</li>'
                    . '<li><strong>Give the microwave a break.</strong> Keep the ' . $link('Dawlance Microwave Oven MD-9 Grill') . ' interior clean and vents clear.</li>'
                    . '<li><strong>Use a stabiliser.</strong> Protect motor-driven appliances from voltage swings.</li>'
                    . '</ol>'
                    . '<p>Need a replacement? Browse all ' . $catLink('Home Appliances', 'home-appliances') . '.</p>',
            ],
        ];

        foreach ($posts as $i => $data) {
            $post = BlogPost::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $data['body'],
                    'status' => 'published',
                    'published_at' => now()->subDays($i * 4 + 1),
                    'meta_title' => $data['title'],
                    'meta_description' => $data['excerpt'],
                    'no_index' => false,
                ],
            );

            $post->categories()->sync(collect($data['categories'])->map(fn ($n) => $categories[$n]->id)->all());
            $post->tags()->sync(collect($data['tags'])->map(fn ($n) => $tags[$n]->id)->all());
        }
    }
}
