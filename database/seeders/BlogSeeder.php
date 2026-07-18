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
 * Blog content tied to the real catalogue (CatalogSeeder): appliance-focused
 * categories, a tag cloud, and 50 published posts whose bodies deep-link to the
 * actual product and category pages. Idempotent (updateOrCreate by slug; pivots
 * synced). Cover images are intentionally left null — attach them in the admin.
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
        foreach ([
            'Buying Guides', 'Product Reviews', 'Tips & Maintenance', 'Energy Saving',
            'Kitchen & Cooking', 'Solar & Backup Power', 'Seasonal Guides',
            'Appliance Safety', 'News & Offers',
        ] as $i => $name) {
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
            'Microwave Oven', 'Water Dispenser', 'Kitchen Hood', 'Built-In Hob', 'Cooking Range',
            'Cooktop', 'Blender & Juicer', 'Iron', 'Patio Heater', 'LPG', 'Gas Safety',
            'Solar Panel', 'Solar Fan', 'Comparison', 'Kitchen', 'Outdoor Living', 'Cleaning',
            'New Arrivals', 'Lahore', 'Prices',
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

        // Posts — 60 in total, Lahore-local first (newest); published_at spreads
        // ~6 months back at 3-day intervals.
        $posts = array_merge(
            $this->lahoreLocalPosts($link, $catLink),
            $this->corePosts($link, $catLink),
            $this->buyingGuidePosts($link, $catLink),
            $this->reviewPosts($link, $catLink),
            $this->maintenancePosts($link, $catLink),
            $this->energyAndSolarPosts($link, $catLink),
            $this->kitchenPosts($link, $catLink),
            $this->seasonalSafetyNewsPosts($link, $catLink),
        );

        // Local-SEO outro appended to every post (rotating so the copy varies).
        // The store is Lahore-based (footer address default) — this anchors each
        // article to the city it mainly serves.
        $lahoreOutros = [
            '<p><em>Shopping from Lahore? We are based right here and deliver across the city — DHA, Gulberg, Johar Town, Model Town and beyond — with shipping all over Pakistan.</em></p>',
            '<p><em>From Gulberg to Bahria Town, we deliver everywhere in Lahore — and to every other city in Pakistan too.</em></p>',
            '<p><em>Our Lahore customers enjoy fast citywide delivery, and we ship nationwide — order online or get in touch for guidance.</em></p>',
            '<p><em>Based in Lahore, serving all of Pakistan — check the product pages above for live prices and stock.</em></p>',
        ];

        foreach ($posts as $i => $data) {
            $post = BlogPost::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $data['body'] . $lahoreOutros[$i % count($lahoreOutros)],
                    'status' => 'published',
                    'published_at' => now()->subDays($i * 3 + 1),
                    'meta_title' => $data['title'],
                    'meta_description' => $data['excerpt'],
                    'no_index' => false,
                ],
            );

            $post->categories()->sync(collect($data['categories'])->map(fn ($n) => $categories[$n]->id)->all());
            $post->tags()->sync(collect($data['tags'])->map(fn ($n) => $tags[$n]->id)->all());
        }
    }

    /**
     * Lahore-targeted local-SEO posts (10) — the "best X in Lahore" searches the
     * store's main audience actually types. Listed first so they publish newest.
     */
    private function lahoreLocalPosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'Best Air Coolers in Lahore (2026): Top Picks for the City Heat',
                'excerpt' => 'Lahore summers demand serious cooling. The room and tower air coolers that actually cope with 45°C afternoons — and where to buy them in the city.',
                'categories' => ['Buying Guides', 'Seasonal Guides'],
                'tags' => ['Lahore', 'Air Cooler', 'Summer', 'Prices', 'Buying Guide'],
                'body' => '<p>By mid-May, Lahore is one of the hottest big cities on earth — and with electricity prices where they are, an air cooler remains the smartest cooling money can buy here. These are the models Lahoris pick most, and why.</p>'
                    . '<h2>Best for bedrooms and lounges</h2>'
                    . '<p>The ' . $link('Super Asia Room Air Cooler ECM-4000') . ' has been a Lahore household name for years — big tank, dependable pump, easy pad replacements before every season.</p>'
                    . '<h2>Best for large halls</h2>'
                    . '<p>The ' . $link('Boss Room Air Cooler ECM-9000 Icy Cool') . ' moves noticeably more air — the right call for open-plan lounges in Johar Town and Valencia\'s bigger homes.</p>'
                    . '<h2>Best for flats and rented rooms</h2>'
                    . '<p>No window mount needed: the ' . $link('Global High Chill Tower Air Cooler with Ice Box & Remote') . ' rolls from room to room and its ice box earns its keep on the worst June evenings.</p>'
                    . '<h2>Buying in Lahore</h2>'
                    . '<p>Skip the Hall Road bargaining — every model above shows its live price and stock online, with delivery across the city. Browse the full ' . $catLink('Air Coolers', 'air-cooler') . ' range before the first heatwave empties the market.</p>',
            ],
            [
                'title' => 'Geyser Prices in Lahore: Instant, Electric and Gas Options for Winter',
                'excerpt' => 'Lahore winters are short but cold, and gas pressure drops exactly when you need it. The geyser types that work best across the city.',
                'categories' => ['Buying Guides', 'Seasonal Guides'],
                'tags' => ['Lahore', 'Geyser', 'Winter', 'Prices'],
                'body' => '<p>Every December, Lahore\'s gas pressure falls just as the fog rolls in. Choosing the right geyser type for your area of the city matters more than the brand.</p>'
                    . '<h2>Low gas pressure areas (old city, dense blocks)</h2>'
                    . '<p>If your burner barely lights at 7 am, go electric: the ' . $link('PEL Electric Storage Geyser 30 Gallon') . ' heats overnight on off-peak supply and holds it for the morning rush.</p>'
                    . '<h2>Good pressure areas (DHA, Bahria, newer societies)</h2>'
                    . '<p>An instant unit like the ' . $link('Glam Gas Instant Gas Water Heater (Tankless Geyser)') . ' or the compact ' . $link('Boss Instant Gas Geyser 6L') . ' gives endless hot water with zero standby loss.</p>'
                    . '<h2>Large families</h2>'
                    . '<p>The ' . $link('Super Asia Gas Geyser 35 Gallon') . ' storage tank serves back-to-back showers — the joint-family classic.</p>'
                    . '<p>Prices update live on each product page — compare the whole ' . $catLink('Geysers', 'geysers') . ' range and order before the December rush.</p>',
            ],
            [
                'title' => 'Solar Panel Prices in Lahore: Planning a Rooftop System in 2026',
                'excerpt' => 'LESCO bills keep climbing while panel prices keep falling. What a 550W panel costs in Lahore and how many your roof needs.',
                'categories' => ['Solar & Backup Power', 'Buying Guides'],
                'tags' => ['Lahore', 'Solar Panel', 'Solar', 'Prices', 'Energy Saving'],
                'body' => '<p>Drive through any Lahore society and count the rooftops — solar has gone from novelty to default here faster than anywhere else in Pakistan. The economics explain why.</p>'
                    . '<h2>The Lahore case for solar</h2>'
                    . '<p>LESCO\'s upper slabs are punishing, and Lahore gets strong sun eight months a year. A correctly sized system typically pays for itself in 3–4 years of avoided bills — faster if you qualify for net metering and export your surplus.</p>'
                    . '<h2>Panel choice</h2>'
                    . '<p>Tier-1 modules are worth the small premium: the ' . $link('Longi Hi-MO 550W Monocrystalline Mono PERC Solar Panel') . ' is the installer favourite across the city, with the ' . $link('Homage Solar Panel 550W Mono PERC') . ' a solid alternative in the same wattage class.</p>'
                    . '<h2>Quick sizing for a Lahore home</h2>'
                    . '<p>A typical 1-kanal house drawing 20–25 units a day needs 9–11 panels (a 5–6 kW array). Confirm your roof has ~2.2 m² of unshaded space per panel — water tanks and parapet shadows are the usual thieves.</p>'
                    . '<p>Live panel prices are on the ' . $catLink('Solar Plates', 'solar-plates') . ' pages — delivery across Lahore included.</p>',
            ],
            [
                'title' => 'Built-In Hobs and Kitchen Hoods in Lahore: The Modern Kitchen Shortlist',
                'excerpt' => 'Renovating a kitchen in Lahore? The built-in hob and chimney combinations that suit the city\'s cooking — and where to get them delivered.',
                'categories' => ['Kitchen & Cooking', 'Buying Guides'],
                'tags' => ['Lahore', 'Built-In Hob', 'Kitchen Hood', 'Kitchen', 'Prices'],
                'body' => '<p>Kitchen renovations lead every Lahore home-improvement list, and the hob-plus-chimney combination is the heart of the job. Here is the shortlist we recommend to customers across the city.</p>'
                    . '<h2>The statement kitchen</h2>'
                    . '<p>The golden-glass ' . $link('Choice Appliances Premium Tri-Series 3-Burner Built-In Gas Hob (GL 308 DG BR Golden)') . ' under the ' . $link('Choice Appliances Premium Curved Glass Kitchen Range Hood (Chimney)') . ' — the pairing that gets photographed for the family WhatsApp group.</p>'
                    . '<h2>The heavy-duty daily kitchen</h2>'
                    . '<p>Lahori cooking is tarka-heavy; the ' . $link('Ideal Appliances Premium 3-Burner Built-In Stainless Steel Gas Hob') . ' under the auto-clean ' . $link('Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)') . ' takes the daily punishment and cleans itself afterwards.</p>'
                    . '<h2>Fitting notes for Lahore homes</h2>'
                    . '<p>Most city kitchens run Sui gas with LPG backup — every hob above works on both. Measure the cutout before ordering and duct the hood to an outside wall. Full ranges: ' . $catLink('Built-In Hobs', 'built-in-hobs') . ' and ' . $catLink('Kitchen Hoods', 'kitchen-hoods') . '.</p>',
            ],
            [
                'title' => 'Washing Machine Prices in Lahore: Single-Tub Models Compared',
                'excerpt' => 'The single-tub washer is Lahore\'s laundry workhorse. Every Asia and Pak model in our range, from starter 8 kg to steel-body 12 kg.',
                'categories' => ['Buying Guides'],
                'tags' => ['Lahore', 'Washing Machine', 'Prices', 'Comparison'],
                'body' => '<p>Walk any Sunday bazaar in Lahore and you will hear the same question: automatic or single-tub? For most of the city, the single-tub still wins on price, repairs and sheer wash power. Here is the value ladder.</p>'
                    . '<h2>Under Rs 20,000</h2>'
                    . '<p>The ' . $link('Asia Single Tub Semi-Automatic Washing Machine SA-210') . ' — 8 kg and a copper motor at the friendliest price in the range.</p>'
                    . '<h2>The mid range</h2>'
                    . '<p>The ' . $link('Asia Classic Wash Single Tub Washing Machine SA-220') . ' and metal-bodied ' . $link('Pak Copper Rust Proof Metal Body Washing Machine PK-980') . ' both wash 10 kg loads; the ' . $link('Asia Super Wash Single Tub Washing Machine SA-240') . ' adds shower-wash for visibly cleaner results.</p>'
                    . '<h2>The flagship</h2>'
                    . '<p>The ' . $link('Asia Smart Wash Steel Body Single Tub Washing Machine SA-260') . ' — 12 kg, stainless body, and up to 40% energy saving for big Lahore households.</p>'
                    . '<p>Live prices on every product page in ' . $catLink('Washing Machines', 'washing-machine') . ', with delivery across the city.</p>',
            ],
            [
                'title' => 'Patio Heater Season in Lahore: Warm Rooftops From DHA to Bahria Town',
                'excerpt' => 'Lahore\'s best winter evenings happen outdoors. The tabletop, mushroom and pyramid heaters that keep rooftop dinners going till midnight.',
                'categories' => ['Seasonal Guides', 'Buying Guides'],
                'tags' => ['Lahore', 'Patio Heater', 'Winter', 'Outdoor Living'],
                'body' => '<p>December in Lahore is rooftop season — barbecues in the fog, chai at midnight, wedding events on every lawn. A gas patio heater is what keeps everyone outside.</p>'
                    . '<h2>Balcony and small rooftop</h2>'
                    . '<p>The ' . $link('Umbrella-Style Stainless Steel Tabletop Mini Gas Patio Heater') . ' sits on the table itself — right-sized for a Gulberg apartment terrace.</p>'
                    . '<h2>Lawn dinners and dawats</h2>'
                    . '<p>The ' . $link('Full-Size Mushroom/Umbrella Style Outdoor Gas Patio Heater') . ' warms a 4–5 metre circle — one unit per seating cluster is the caterer\'s rule across DHA lawns.</p>'
                    . '<h2>Restaurants and premium terraces</h2>'
                    . '<p>The ' . $link('Pyramid-Style Glass Tube Outdoor Gas Patio Heater') . ' turns the heat itself into decor — its dancing flame is why M.M. Alam Road terraces favour the style.</p>'
                    . '<p>All three run on standard cylinders from ' . $catLink('LPG Cylinders', 'lpg-cylinders') . '. See the full ' . $catLink('Patio Heaters', 'patio-heaters') . ' range before the season sells through.</p>',
            ],
            [
                'title' => 'Best Fans for Lahore Load-Shedding: Hybrid Ceiling and 12V Solar Picks',
                'excerpt' => 'When LESCO cuts the power on a June night, the right fan keeps spinning. Lahore\'s best AC/DC hybrid and solar fan options.',
                'categories' => ['Solar & Backup Power', 'Buying Guides'],
                'tags' => ['Lahore', 'Fans', 'Load Shedding', 'Inverter', 'Solar Fan'],
                'body' => '<p>Nothing tests a Lahore summer like a 2 am outage. The fix is not a bigger UPS — it is fans that barely sip the battery you already own.</p>'
                    . '<h2>The ceiling upgrade</h2>'
                    . '<p>The ' . $link('GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)') . ' draws 30–40W and switches to battery without a flicker — one UPS now carries every bedroom through the night. The ' . $link('PEL DC Inverter Ceiling Fan SmartSaver') . ' does the same duty in a classic design.</p>'
                    . '<h2>The portable option</h2>'
                    . '<p>For shops, garages and dera rooms, the ' . $link('Sogo 18-Inch 12V DC Solar Pedestal Fan (Rechargeable, AC/DC/Solar)') . ' runs straight off a 12V battery or a single solar panel — no inverter, no installation.</p>'
                    . '<h2>The maths for Lahore</h2>'
                    . '<p>Swap three 95W fans for 35W hybrids and an 8-month season returns roughly 500 saved units — before counting the smaller UPS you no longer need. Start in ' . $catLink('DC Fans', 'dc-fans') . ' and ' . $catLink('Solar Fans', 'solar-fans') . '.</p>',
            ],
            [
                'title' => 'LPG Cylinder Prices in Lahore: Steel vs Composite for City Homes',
                'excerpt' => 'Plenty of Lahore households run on LPG year-round. What cylinders cost, which type suits flats vs houses, and the safety gear to pair.',
                'categories' => ['Buying Guides', 'Appliance Safety'],
                'tags' => ['Lahore', 'LPG', 'Prices', 'Gas Safety'],
                'body' => '<p>Between winter pressure drops and areas Sui gas never reached, LPG is a fact of Lahore life — in Cantt flats, commercial kitchens and half the city\'s rooftops every December.</p>'
                    . '<h2>The budget choice</h2>'
                    . '<p>The ' . $link('Pak Gas Domestic LPG Gas Cylinder (11.8 kg)') . ' is the standard steel household cylinder — cheapest to buy, refillable at any authorised shop in the city.</p>'
                    . '<h2>The flat-friendly upgrade</h2>'
                    . '<p>Carrying steel up apartment stairs is misery. The ' . $link('Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)') . ' weighs about half as much, cannot rust in monsoon humidity, and its translucent body shows the gas level before a dinner party — not during it.</p>'
                    . '<h2>Do not skip the regulator</h2>'
                    . '<p>A proper low-pressure regulator like the ' . $link('Super Gree Clip-On Low-Pressure Gas Regulator') . ' is the cheapest safety device you will ever buy. See everything under ' . $catLink('Gas Appliances', 'gas-appliances') . '.</p>',
            ],
            [
                'title' => 'Microwave Oven Deals in Lahore: 20-Litre Solo Models Worth Buying',
                'excerpt' => 'The 20L solo microwave is Lahore\'s kitchen default. The Haier and Dawlance models to shortlist and what they cost right now.',
                'categories' => ['Buying Guides', 'Kitchen & Cooking'],
                'tags' => ['Lahore', 'Microwave Oven', 'Prices', 'Kitchen'],
                'body' => '<p>Every Lahore kitchen eventually lands on the same appliance: a 20-litre solo microwave for reheating salan, softening butter for parathas and defrosting the freezer\'s qeema stash. Two models dominate the city\'s shortlists.</p>'
                    . '<h2>The warranty pick</h2>'
                    . '<p>The ' . $link('Haier 20 Litre Solo Microwave Oven (HDL-20MXP7)') . ' — 700W, six power levels and a 2-year warranty, backed by Haier\'s wide Lahore service network.</p>'
                    . '<h2>The value pick</h2>'
                    . '<p>The ' . $link('Dawlance 20 Litre Solo Microwave Oven (DW-MD 15 Solo White)') . ' — same 700W output with a stainless interior, usually at the lower price. Dawlance service centres cover every corner of the city.</p>'
                    . '<h2>Need more than reheating?</h2>'
                    . '<p>Step up to the ' . $link('Dawlance Microwave Oven MD-9 Grill') . ' for grilling on top of the basics. All live prices in ' . $catLink('Microwave Ovens', 'microwave-ovens') . ' — delivered across Lahore.</p>',
            ],
            [
                'title' => 'Water Dispensers in Lahore: Cold Water for Homes, Offices and Shops',
                'excerpt' => 'Lahore runs on 19-litre bottles eight months a year. The dispenser types that fit a Gulberg office, a family home or a shop counter.',
                'categories' => ['Buying Guides'],
                'tags' => ['Lahore', 'Water Dispenser', 'Summer', 'Prices'],
                'body' => '<p>From March to October, the 19-litre bottle is Lahore\'s most traded commodity. The dispenser you pair with it decides whether the water is actually cold when the delivery bell rings at 2 pm.</p>'
                    . '<h2>For the shop or small office</h2>'
                    . '<p>The countertop ' . $link('PEL Table-Top Classic 115 Water Dispenser') . ' serves hot and normal water without claiming floor space — the Liberty Market shop-counter classic.</p>'
                    . '<h2>For the family home</h2>'
                    . '<p>The ' . $link('Orient 3-Tap Water Dispenser Icon') . ' adds a proper cold tap for the summer and stands at a comfortable height for children.</p>'
                    . '<h2>For maximum value</h2>'
                    . '<p>The ' . $link('Choice Appliances Floor-Standing Water Dispenser with Built-In Mini Refrigerator') . ' chills drinks and snacks in its lower cabinet — one plug doing two appliances\' work in an office kitchen.</p>'
                    . '<p>High demand? A stainless ' . $link('Waves Electric Water Cooler 65L') . ' from ' . $catLink('Water Coolers', 'water-cooler') . ' serves a whole floor. Everything else is in ' . $catLink('Water Dispensers', 'water-dispenser') . '.</p>',
            ],
        ];
    }

    /** The original launch posts (kept verbatim so their slugs stay stable). */
    private function corePosts(\Closure $link, \Closure $catLink): array
    {
        return [
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
    }

    /** Buying guides for the extended catalogue (9 posts). */
    private function buyingGuidePosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'Haier vs Dawlance: Which 20-Litre Solo Microwave Should You Buy?',
                'excerpt' => 'Two of the most popular 700W solo microwaves in Pakistan, compared on power levels, build and price so you can pick with confidence.',
                'categories' => ['Buying Guides'],
                'tags' => ['Microwave Oven', 'Comparison', 'Buying Guide', 'Kitchen'],
                'body' => '<p>For reheating rotis, defrosting mince and warming leftovers, a 20-litre solo microwave is all most kitchens need. Two models dominate this segment in Pakistan — and they are closer than you might think.</p>'
                    . '<h2>The contenders</h2>'
                    . '<p>The ' . $link('Haier 20 Litre Solo Microwave Oven (HDL-20MXP7)') . ' offers 700W of power with 6 power levels, speed and weight defrost, and a 2-year warranty. The ' . $link('Dawlance 20 Litre Solo Microwave Oven (DW-MD 15 Solo White)') . ' matches the 700W output with 5 power levels, a 30-minute timer and a stainless steel interior.</p>'
                    . '<h2>How to decide</h2>'
                    . '<ul>'
                    . '<li><strong>Warranty:</strong> Haier gives 2 years to Dawlance\'s 1 — worth weighing.</li>'
                    . '<li><strong>Interior:</strong> Dawlance\'s stainless cavity resists staining and wipes clean easily.</li>'
                    . '<li><strong>Price:</strong> the Dawlance usually lands a little cheaper — a genuine bargain on sale.</li>'
                    . '</ul>'
                    . '<p>Either way you get dependable everyday heating. Browse all ' . $catLink('Microwave Ovens', 'microwave-ovens') . ' to compare prices today.</p>',
            ],
            [
                'title' => 'Built-In Gas Hob Buying Guide: Burners, Glass Tops and Safety Features',
                'excerpt' => 'From single-burner units to golden glass showpieces — what to look for when choosing a built-in hob for a modern Pakistani kitchen.',
                'categories' => ['Buying Guides', 'Kitchen & Cooking'],
                'tags' => ['Built-In Hob', 'Kitchen', 'Buying Guide', 'Gas Safety'],
                'body' => '<p>Built-in hobs have become the centrepiece of modern Pakistani kitchens — sleek, easy to clean and far safer than loose stoves. Here is what separates a good one from a great one.</p>'
                    . '<h2>The checklist</h2>'
                    . '<ul>'
                    . '<li><strong>Brass burners</strong> outlast aluminium and hold a steadier flame.</li>'
                    . '<li><strong>Flame-failure device (FFD)</strong> cuts the gas if the flame blows out — non-negotiable with children at home.</li>'
                    . '<li><strong>Auto-ignition</strong> means no matches and no clicking lighters.</li>'
                    . '<li><strong>Toughened glass or stainless top</strong> — glass looks premium; steel shrugs off heavy handling.</li>'
                    . '</ul>'
                    . '<h2>Our picks at every size</h2>'
                    . '<p>For a statement kitchen, the ' . $link('Choice Appliances Premium Tri-Series 3-Burner Built-In Gas Hob (GL 308 DG BR Golden)') . ' pairs golden glass with three brass burners. Prefer steel? The ' . $link('Ideal Appliances Premium 3-Burner Built-In Stainless Steel Gas Hob') . ' is the workhorse choice. Tight on space, the ' . $link('Choice Appliances Single-Burner Built-In Gas Hob') . ' slots in anywhere.</p>'
                    . '<p>See every model in ' . $catLink('Built-In Hobs', 'built-in-hobs') . '.</p>',
            ],
            [
                'title' => 'Kitchen Chimney Buying Guide: Suction Power, Filters and Auto-Clean',
                'excerpt' => 'Pakistani cooking is hard on kitchens. Here is how to size a range hood, what suction rating you need, and when auto-clean is worth it.',
                'categories' => ['Buying Guides', 'Kitchen & Cooking'],
                'tags' => ['Kitchen Hood', 'Kitchen', 'Buying Guide'],
                'body' => '<p>Tarka, frying and high-heat cooking fill a kitchen with oil-laden smoke that settles on every surface. A properly sized chimney (range hood) keeps walls, cabinets and lungs clean.</p>'
                    . '<h2>Get the suction right</h2>'
                    . '<p>For Pakistani cooking, look for at least <strong>1000–1400 m³/hr</strong> of suction. Anything less struggles with daily frying. Baffle filters made of stainless steel handle heavy oil far better than mesh.</p>'
                    . '<h2>Auto-clean or manual?</h2>'
                    . '<p>An auto-clean hood like the ' . $link('Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)') . ' flushes trapped grease into a collector at the press of a button — a huge time saver. If your budget is tighter, the ' . $link('Choice Appliances Premium Curved Glass Kitchen Range Hood (Chimney)') . ' delivers strong 1100 m³/hr suction with easy lift-out baffle filters.</p>'
                    . '<p>Compare both in ' . $catLink('Kitchen Hoods', 'kitchen-hoods') . ' — your paint will thank you.</p>',
            ],
            [
                'title' => '3-Burner or 5-Burner? Choosing the Right Cooking Range for Your Family',
                'excerpt' => 'A free-standing cooking range is the heart of a busy kitchen. Here is how to pick between compact 3-burner and full-size 5-burner models.',
                'categories' => ['Buying Guides', 'Kitchen & Cooking'],
                'tags' => ['Cooking Range', 'Kitchen', 'Buying Guide', 'Comparison'],
                'body' => '<p>A free-standing cooking range gives you burners, oven and grill in one appliance — no carpentry, no built-in cabinets, just wheel it in and cook.</p>'
                    . '<h2>Compact: 3 burners</h2>'
                    . '<p>The ' . $link('National Free-Standing 3-Burner Cooking Range with Oven & Grill') . ' fits smaller kitchens while still packing a full gas oven with grill — ideal for families of 3–5 who bake occasionally.</p>'
                    . '<h2>Full-size: 5 burners</h2>'
                    . '<p>Cooking for a joint family, or love hosting dawats? The ' . $link('National Premium 5-Burner Double-Door Cooking Range with Oven & Grill') . ' runs five dishes at once over a double-door oven with rotisserie, wrapped in a stainless steel body built for years of heat.</p>'
                    . '<h2>Quick rule of thumb</h2>'
                    . '<p>Count how many pots are on your stove at dinner time. Three or fewer — save money and space with the 3-burner. Four or more — the 5-burner pays for itself in convenience.</p>'
                    . '<p>See both in ' . $catLink('Cooking Ranges', 'cooking-ranges') . '.</p>',
            ],
            [
                'title' => 'Water Dispenser Buying Guide: Table-Top, Floor-Standing or With a Fridge?',
                'excerpt' => 'Two taps or three? Compressor or electric cooling? A practical guide to picking the right water dispenser for your home or office.',
                'categories' => ['Buying Guides'],
                'tags' => ['Water Dispenser', 'Home Appliances', 'Buying Guide'],
                'body' => '<p>A water dispenser saves the daily hassle of boiling and chilling water — but the range runs from compact counter units to full cabinets with built-in fridges. Here is how to choose.</p>'
                    . '<h2>Table-top: small spaces</h2>'
                    . '<p>The ' . $link('PEL Table-Top Classic 115 Water Dispenser') . ' serves hot and normal water from a countertop footprint — perfect for offices, shops and small kitchens.</p>'
                    . '<h2>Floor-standing: families</h2>'
                    . '<p>A classic 3-tap unit like the ' . $link('Orient 3-Tap Water Dispenser Icon') . ' adds cold water and holds a full 19-litre bottle at a comfortable height.</p>'
                    . '<h2>With a mini fridge: maximum value</h2>'
                    . '<p>The ' . $link('Choice Appliances Floor-Standing Water Dispenser with Built-In Mini Refrigerator') . ' turns the empty cabinet space into chilled storage for drinks and snacks — one plug, two appliances.</p>'
                    . '<p>Compare all models in ' . $catLink('Water Dispensers', 'water-dispenser') . '.</p>',
            ],
            [
                'title' => 'Single-Tub Washing Machines: The Complete Asia and Pak Model Guide',
                'excerpt' => 'From the budget SA-210 to the steel-body SA-260 — every single-tub washer in our range compared by capacity, motor and build.',
                'categories' => ['Buying Guides'],
                'tags' => ['Washing Machine', 'Buying Guide', 'Comparison', 'Home Appliances'],
                'body' => '<p>The humble single-tub washer remains the best-selling machine in Pakistan for a simple reason: it washes hard, sips electricity and costs a fraction of an automatic. Here is our range, bottom to top.</p>'
                    . '<h2>The ladder</h2>'
                    . '<ul>'
                    . '<li><strong>Starter:</strong> the ' . $link('Asia Single Tub Semi-Automatic Washing Machine SA-210') . ' — 8 kg, copper motor, unbeatable price.</li>'
                    . '<li><strong>Step up:</strong> the ' . $link('Asia Classic Wash Single Tub Washing Machine SA-220') . ' — 10 kg with heavy-gear drive and double pulsator.</li>'
                    . '<li><strong>Sweet spot:</strong> the ' . $link('Asia Super Wash Single Tub Washing Machine SA-240') . ' adds shower-wash technology for deeper cleaning.</li>'
                    . '<li><strong>Metal body:</strong> the ' . $link('Pak Copper Rust Proof Metal Body Washing Machine PK-980') . ' — powder-coated steel for rough use.</li>'
                    . '<li><strong>Flagship:</strong> the ' . $link('Asia Smart Wash Steel Body Single Tub Washing Machine SA-260') . ' — 12 kg, stainless body, up to 40% energy saving.</li>'
                    . '</ul>'
                    . '<p>Every model carries a 1-year warranty. See them side by side in ' . $catLink('Washing Machines', 'washing-machine') . '.</p>',
            ],
            [
                'title' => 'Steel vs Composite LPG Cylinders: Which Is Right for Your Home?',
                'excerpt' => 'The classic steel cylinder is cheap; the new composite fiber cylinder is light, rust-proof and explosion-free. We weigh both options.',
                'categories' => ['Buying Guides', 'Appliance Safety'],
                'tags' => ['LPG', 'Gas Safety', 'Buying Guide', 'Comparison'],
                'body' => '<p>If your area runs on LPG, the cylinder itself matters more than most people think — for your back, your wallet and your safety.</p>'
                    . '<h2>The proven steel workhorse</h2>'
                    . '<p>The ' . $link('Pak Gas Domestic LPG Gas Cylinder (11.8 kg)') . ' is the standard 11.8 kg household size: heavy-gauge welded steel, brass valve, refillable at any authorised distributor, and the cheapest way to get started.</p>'
                    . '<h2>The composite upgrade</h2>'
                    . '<p>The ' . $link('Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)') . ' weighs roughly half as much, cannot rust, shows the gas level through its translucent body and is engineered to be explosion-free — with a 5-year warranty behind it.</p>'
                    . '<h2>Bottom line</h2>'
                    . '<p>On a tight budget, steel does the job. If you carry the cylinder upstairs, or safety is the priority, the composite pays for its premium every single refill.</p>'
                    . '<p>Both are in stock under ' . $catLink('LPG Cylinders', 'lpg-cylinders') . ' — and grab a ' . $link('Super Gree Clip-On Low-Pressure Gas Regulator') . ' while you are at it.</p>',
            ],
            [
                'title' => 'Outdoor Patio Heater Guide: Tabletop, Mushroom and Pyramid Styles Compared',
                'excerpt' => 'Planning winter gatherings on the lawn or rooftop? Here is how the three patio heater styles differ in heat, looks and running cost.',
                'categories' => ['Buying Guides', 'Seasonal Guides'],
                'tags' => ['Patio Heater', 'Winter', 'Outdoor Living', 'Buying Guide'],
                'body' => '<p>Nothing extends a winter evening like radiant outdoor heat. Gas patio heaters come in three distinct styles — each suits a different setting.</p>'
                    . '<h2>Tabletop: intimate and portable</h2>'
                    . '<p>The ' . $link('Umbrella-Style Stainless Steel Tabletop Mini Gas Patio Heater') . ' sits at the centre of a table and warms the circle around it — ideal for balconies and small rooftops.</p>'
                    . '<h2>Mushroom: the classic crowd-warmer</h2>'
                    . '<p>The ' . $link('Full-Size Mushroom/Umbrella Style Outdoor Gas Patio Heater') . ' pushes out up to 13 kW across a 4–5 metre radius — the workhorse of lawns, cafes and wedding marquees.</p>'
                    . '<h2>Pyramid: heat as a centrepiece</h2>'
                    . '<p>The ' . $link('Pyramid-Style Glass Tube Outdoor Gas Patio Heater') . ' delivers the same warmth with a mesmerising dancing flame in a quartz tube — the choice for upscale terraces and restaurants.</p>'
                    . '<p>All three run on standard LPG cylinders. Compare them in ' . $catLink('Patio Heaters', 'patio-heaters') . '.</p>',
            ],
            [
                'title' => 'Tabletop Gas Stove Guide: One Burner or Two, and Which Brand to Trust',
                'excerpt' => 'Hostels, small kitchens and backup cooking all call for a tabletop stove. Here is what to check before you buy one.',
                'categories' => ['Buying Guides', 'Kitchen & Cooking'],
                'tags' => ['Stove', 'Kitchen', 'Buying Guide'],
                'body' => '<p>A tabletop gas stove is the most flexible cooker you can own — it moves house with you, works on LPG or Sui gas, and doubles as a backup when the main kitchen is busy.</p>'
                    . '<h2>What to check</h2>'
                    . '<ul>'
                    . '<li><strong>Brass burners</strong> — they resist warping and burn a cleaner blue flame.</li>'
                    . '<li><strong>Piezo auto-ignition</strong> — no matches, instant lighting.</li>'
                    . '<li><strong>Cast-iron trivets</strong> — pressure cookers and karahis need the stability.</li>'
                    . '</ul>'
                    . '<h2>Our range</h2>'
                    . '<p>For solo cooking, the ' . $link('Shanghai Single-Burner Stainless Steel Tabletop Gas Stove') . ' is compact and famously affordable. Families should look at the ' . $link('Shanghai Superior Quality 2-Burner Stainless Steel Tabletop Gas Stove') . ' or the ' . $link('Super Fire Gas 2-Burner Stainless Steel Tabletop Gas Stove') . ' — both give you two strong brass burners on an easy-clean steel deck.</p>'
                    . '<p>Browse every model in ' . $catLink('Stoves', 'stoves') . '.</p>',
            ],
        ];
    }

    /** In-depth single-product reviews (8 posts). */
    private function reviewPosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'Review: Asia Super Wash SA-240 — The Single-Tub Sweet Spot',
                'excerpt' => 'Shower-wash technology, a strong copper motor and 10 kg of capacity. We put the SA-240 through two weeks of family laundry.',
                'categories' => ['Product Reviews'],
                'tags' => ['Washing Machine', 'Reviews', 'Home Appliances'],
                'body' => '<p>Asia\'s single-tub range covers everything from starter machines to steel-body flagships, but the ' . $link('Asia Super Wash Single Tub Washing Machine SA-240') . ' sits at the point where price and features balance best.</p>'
                    . '<h2>What stood out</h2>'
                    . '<ul>'
                    . '<li><strong>Shower wash:</strong> water circulates from above as the pulsator spins, so detergent reaches every layer — visibly cleaner collars and cuffs.</li>'
                    . '<li><strong>Build:</strong> the double plastic body shrugged off knocks and sun exposure on a rooftop washing area.</li>'
                    . '<li><strong>Running cost:</strong> at roughly 350W it barely registers on the bill, and it happily runs from a modest UPS.</li>'
                    . '</ul>'
                    . '<h2>What could be better</h2>'
                    . '<p>Like all single-tubs, you wring and rinse by hand — if that is a deal-breaker, budget for an automatic instead.</p>'
                    . '<h2>Verdict</h2>'
                    . '<p>For a family of 4–6 that wants serious wash power without the automatic price tag, the SA-240 is the machine to beat. See alternatives in ' . $catLink('Washing Machines', 'washing-machine') . '.</p>',
            ],
            [
                'title' => 'Review: BGC Composite Fiber Cylinder — The Last LPG Cylinder You Will Buy',
                'excerpt' => 'Half the weight, zero rust, a visible gas level and an explosion-free design. The BGC composite cylinder reviewed in daily use.',
                'categories' => ['Product Reviews', 'Appliance Safety'],
                'tags' => ['LPG', 'Gas Safety', 'Reviews'],
                'body' => '<p>Steel cylinders have served Pakistani kitchens for decades, but lifting one up three flights of stairs makes you question tradition. Enter the ' . $link('Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)') . '.</p>'
                    . '<h2>Living with it</h2>'
                    . '<p>At roughly 5.5 kg empty, it is genuinely a one-hand carry. The translucent body ends the classic guessing game — you can <em>see</em> how much gas remains before a dinner party, not discover it mid-karahi.</p>'
                    . '<h2>The safety story</h2>'
                    . '<p>Fiberglass and HDPE construction cannot rust, and the design is engineered to be explosion-free: in a fire the material softens and vents rather than fragmenting. OGRA approval and a 5-year warranty back it up.</p>'
                    . '<h2>Verdict</h2>'
                    . '<p>It costs more than steel up front, but the weight saving, safety margin and visible gas level make it the clear long-term choice. Pair it with a quality regulator from ' . $catLink('Gas Regulators', 'gas-regulators') . '.</p>',
            ],
            [
                'title' => 'Review: Glam Gas Wave Chimney — Auto-Clean That Actually Works',
                'excerpt' => 'Gesture control, 1400 m³/hr suction and one-touch auto-clean. We tested the Wave Series hood over a month of heavy tarka cooking.',
                'categories' => ['Product Reviews', 'Kitchen & Cooking'],
                'tags' => ['Kitchen Hood', 'Kitchen', 'Reviews'],
                'body' => '<p>Most kitchen hoods in this class promise big suction numbers; few survive a Pakistani kitchen\'s daily frying without weekly scrubbing. The ' . $link('Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)') . ' is built for exactly that abuse.</p>'
                    . '<h2>Highlights from testing</h2>'
                    . '<ul>'
                    . '<li><strong>Suction:</strong> 1400 m³/hr clears a smoking karahi in seconds — the kitchen door no longer needs to stay open.</li>'
                    . '<li><strong>Gesture control:</strong> a wave of the hand toggles the blower when your fingers are covered in atta. Gimmick on paper, genuinely useful in practice.</li>'
                    . '<li><strong>Auto-clean:</strong> one press flushes grease into the oil collector. After four weeks of daily cooking the baffles still looked fresh.</li>'
                    . '</ul>'
                    . '<h2>Verdict</h2>'
                    . '<p>Premium price, but it removes the single biggest chore of owning a chimney. If the budget is tighter, see the rest of our ' . $catLink('Kitchen Hoods', 'kitchen-hoods') . ' range.</p>',
            ],
            [
                'title' => 'Review: RAF R.8045 Infrared Cooker — 3500W of Flame-Free Cooking',
                'excerpt' => 'Works with every pot, heats like a gas flame and costs less than a dinner out. The RAF infrared cooker earns its bench space.',
                'categories' => ['Product Reviews', 'Kitchen & Cooking'],
                'tags' => ['Cooktop', 'Kitchen', 'Reviews', 'Load Shedding'],
                'body' => '<p>Induction cookers are efficient but fussy about cookware. The ' . $link('RAF Multifunction Infrared Cooker & Hot Plate R.8045 (3500W)') . ' takes a different route: infrared heat that works with <em>any</em> pot — steel, aluminium, glass, even clay handis.</p>'
                    . '<h2>In the kitchen</h2>'
                    . '<p>The 3500W element boils a litre of water in a few minutes and sears like a strong gas flame. The rotary control sweeps smoothly from a gentle simmer for daal to full heat for frying. The micro-crystal plate wiped clean with one cloth pass after a milk boil-over.</p>'
                    . '<h2>Where it fits</h2>'
                    . '<p>Perfect as a gas-outage backup, a hostel cooker or a second burner on Eid morning. It draws serious wattage at full power, so run it on a direct socket rather than an extension lead.</p>'
                    . '<h2>Verdict</h2>'
                    . '<p>At this price it is the easiest kitchen upgrade we have tested this year. More options in ' . $catLink('Cooktops', 'cooktops') . '.</p>',
            ],
            [
                'title' => 'Review: GFC 56-Inch AC/DC Hybrid Fan — Load-Shedding Insurance on the Ceiling',
                'excerpt' => 'It runs on mains, UPS or a 12V solar battery and sips 30–40W. The GFC hybrid inverter fan reviewed through a week of outages.',
                'categories' => ['Product Reviews', 'Solar & Backup Power'],
                'tags' => ['Fans', 'Inverter', 'Load Shedding', 'Reviews', 'Solar'],
                'body' => '<p>A ceiling fan you never notice is a good ceiling fan. The ' . $link('GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)') . ' spent our review week switching invisibly between mains and battery as the schedule dictated — which is exactly the point.</p>'
                    . '<h2>The numbers that matter</h2>'
                    . '<ul>'
                    . '<li><strong>30–40W draw</strong> against 80–100W for a standard fan — a UPS that ran one old fan now runs two of these with room to spare.</li>'
                    . '<li><strong>370 RPM turbo</strong> mode moves air like a full-size AC fan.</li>'
                    . '<li><strong>Remote control</strong> with speed steps and a timer — no more wall-dimmer hum.</li>'
                    . '</ul>'
                    . '<h2>Verdict</h2>'
                    . '<p>If load-shedding shapes your evenings, replacing even one bedroom fan with this pays off immediately. The 3-year motor warranty seals it. Compare models in ' . $catLink('DC Fans', 'dc-fans') . '.</p>',
            ],
            [
                'title' => 'Review: Longi Hi-MO 550W — The Tier-1 Panel Powering Half of Pakistan\'s Rooftops',
                'excerpt' => 'Around 21% efficiency, half-cut cells and a 25-year performance warranty. Why the Longi 550W is the default choice for home solar.',
                'categories' => ['Product Reviews', 'Solar & Backup Power'],
                'tags' => ['Solar Panel', 'Solar', 'Reviews', 'Energy Saving'],
                'body' => '<p>Ask three solar installers for a quote and odds are at least two will spec this panel. The ' . $link('Longi Hi-MO 550W Monocrystalline Mono PERC Solar Panel') . ' has become the de facto standard for Pakistani rooftops — here is why.</p>'
                    . '<h2>The engineering</h2>'
                    . '<p>Half-cut mono PERC cells cut resistive losses and keep half the panel producing even when the other half is shaded by a water tank or parapet wall. Module efficiency of about 21% means fewer panels for the same output — critical on crowded roofs.</p>'
                    . '<h2>Built for our climate</h2>'
                    . '<p>The anodised aluminium frame and 3.2 mm tempered glass are rated for high wind and hail, and the panel keeps producing usefully in June heat where cheaper modules derate sharply.</p>'
                    . '<h2>Verdict</h2>'
                    . '<p>With a 12-year product and 25-year performance warranty, this is as close to a safe bet as solar gets. Size your system in our ' . $catLink('Solar Plates', 'solar-plates') . ' section.</p>',
            ],
            [
                'title' => 'Review: Global High Chill Tower Cooler — Big Cooling, Small Footprint',
                'excerpt' => 'A 50-litre tank, honeycomb pads, an ice box and a remote — the High Chill tower cooler tested through a Lahore heatwave.',
                'categories' => ['Product Reviews'],
                'tags' => ['Air Cooler', 'Summer', 'Reviews'],
                'body' => '<p>Traditional room coolers work, but they occupy a window and half the room. The ' . $link('Global High Chill Tower Air Cooler with Ice Box & Remote') . ' packs the same cooling into a slim tower that rolls wherever you need it.</p>'
                    . '<h2>Heatwave performance</h2>'
                    . '<p>With the 50-litre tank filled and two ice packs in the box, it dropped our test room by a solid margin within twenty minutes and held it there through a 44°C afternoon. The honeycomb pads wick evenly, so the air feels cool rather than damp.</p>'
                    . '<h2>Daily conveniences</h2>'
                    . '<ul>'
                    . '<li>Remote control for speed, swing and timer from the bed.</li>'
                    . '<li>Castor wheels — lounge by day, bedroom by night.</li>'
                    . '<li>180W draw, comfortably UPS-friendly.</li>'
                    . '</ul>'
                    . '<h2>Verdict</h2>'
                    . '<p>The best pick for renters and anyone short on window space. See the whole ' . $catLink('Air Coolers', 'air-cooler') . ' line-up.</p>',
            ],
            [
                'title' => 'Review: National MJ-176 — Three Kitchen Machines in One Body',
                'excerpt' => 'Juicer, blender and dry mill from one 1000W copper motor. The National MJ-176 reviewed across a month of shakes and masalas.',
                'categories' => ['Product Reviews', 'Kitchen & Cooking'],
                'tags' => ['Blender & Juicer', 'Kitchen', 'Reviews'],
                'body' => '<p>Counter space is precious. The ' . $link('National 3-in-1 Juicer, Blender and Dry Miller (Model MJ-176)') . ' earns its spot by replacing three separate appliances with one 1000W base and a set of swap-on attachments.</p>'
                    . '<h2>How each mode performed</h2>'
                    . '<ul>'
                    . '<li><strong>Juicer:</strong> clear apple and carrot juice with a dry pulp basket — the sign of an extractor doing its job.</li>'
                    . '<li><strong>Blender:</strong> the 1.5L jug crushed ice for oreo shakes without stalling.</li>'
                    . '<li><strong>Dry mill:</strong> whole garam masala to fine powder in under a minute.</li>'
                    . '</ul>'
                    . '<h2>Verdict</h2>'
                    . '<p>For wedding-season shakes and daily chutneys alike, the MJ-176 is the most versatile machine in its price band. Also consider the ' . $link('Panasonic Classic 2-in-1 Blender and Dry Grinder Mill (Model HJ-661)') . ' and ' . $link('Kenwood 2-in-1 Blender and Grinder Mill (Model KW-871)') . ' in ' . $catLink('Blenders & Juicers', 'blenders-juicers') . '.</p>',
            ],
        ];
    }

    /** Care, cleaning and maintenance how-tos (8 posts). */
    private function maintenancePosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'How to Make a Single-Tub Washing Machine Last 10 Years',
                'excerpt' => 'Lint filters, drain care and motor-friendly habits — the maintenance routine that keeps a single-tub washer spinning for a decade.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Washing Machine', 'Maintenance', 'Cleaning'],
                'body' => '<p>A single-tub washer has one motor, one belt and almost nothing else to fail — which is why a little care goes such a long way.</p>'
                    . '<h2>The monthly routine</h2>'
                    . '<ol>'
                    . '<li><strong>Empty the lint filter</strong> after every third wash — a clogged filter recirculates fluff onto clean clothes.</li>'
                    . '<li><strong>Run a plain-water rinse</strong> monthly to flush detergent sludge from under the pulsator.</li>'
                    . '<li><strong>Drain fully and leave the lid open</strong> so the tub dries — trapped moisture is what ages a machine.</li>'
                    . '<li><strong>Check the hose path</strong>: sharp kinks strain the drain and invite leaks.</li>'
                    . '</ol>'
                    . '<h2>Protect the motor</h2>'
                    . '<p>Never overload past the rated kilos, and use a stabiliser if your voltage swings. Machines with copper motors — like the ' . $link('Asia Classic Wash Single Tub Washing Machine SA-220') . ' or the steel-bodied ' . $link('Asia Smart Wash Steel Body Single Tub Washing Machine SA-260') . ' — will reward you with a decade of service.</p>'
                    . '<p>Time for an upgrade instead? Browse ' . $catLink('Washing Machines', 'washing-machine') . '.</p>',
            ],
            [
                'title' => 'Microwave Care 101: Cleaning, Smells and What Never to Put Inside',
                'excerpt' => 'Steam-clean trick, odour removal and the short list of things that damage a magnetron. Keep your microwave safe and spotless.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Microwave Oven', 'Maintenance', 'Cleaning', 'Kitchen'],
                'body' => '<p>A microwave works hard in a Pakistani kitchen — reheating salan, softening butter, defrosting qeema. Five minutes of care a week keeps it safe and smell-free.</p>'
                    . '<h2>The steam-clean trick</h2>'
                    . '<p>Microwave a bowl of water with lemon slices for three minutes, keep the door shut for two more, then wipe. Dried splatter lifts off effortlessly — no chemicals near your food.</p>'
                    . '<h2>Never microwave these</h2>'
                    . '<ul>'
                    . '<li>Metal utensils, foil or gold-rimmed crockery — arcing damages the cavity.</li>'
                    . '<li>Sealed containers and whole eggs — pressure builds and bursts.</li>'
                    . '<li>Empty runs — with nothing to absorb energy, the magnetron cooks itself.</li>'
                    . '</ul>'
                    . '<h2>Keep the vents clear</h2>'
                    . '<p>Leave a hand-width of space around units like the ' . $link('Haier 20 Litre Solo Microwave Oven (HDL-20MXP7)') . ' so heat escapes. Shopping for a new one? See ' . $catLink('Microwave Ovens', 'microwave-ovens') . '.</p>',
            ],
            [
                'title' => 'Cleaning Your Kitchen Chimney: Baffle Filters, Oil Collectors and Auto-Clean',
                'excerpt' => 'A greasy hood loses half its suction. Here is the 15-minute monthly routine that keeps a kitchen chimney pulling at full power.',
                'categories' => ['Tips & Maintenance', 'Kitchen & Cooking'],
                'tags' => ['Kitchen Hood', 'Cleaning', 'Maintenance', 'Kitchen'],
                'body' => '<p>Suction ratings assume clean filters. Let grease build for a season and a 1200 m³/hr hood behaves like a 600 — the smoke simply rolls past it.</p>'
                    . '<h2>Monthly: baffle filters</h2>'
                    . '<p>Slide the stainless baffles out and soak them in hot water with a spoon of dishwashing liquid and washing soda. Fifteen minutes later the grease wipes away. Dry and refit.</p>'
                    . '<h2>Weekly: the oil collector</h2>'
                    . '<p>Empty the collector cup before it overflows — old oil is both a smell and a fire risk.</p>'
                    . '<h2>Or let the hood do it</h2>'
                    . '<p>Auto-clean models like the ' . $link('Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)') . ' heat and flush internal grease to the collector at one press, cutting manual cleaning to a fraction. The ' . $link('Choice Appliances Premium Curved Glass Kitchen Range Hood (Chimney)') . ' keeps it simple with easy lift-out baffles.</p>'
                    . '<p>Compare both in ' . $catLink('Kitchen Hoods', 'kitchen-hoods') . '.</p>',
            ],
            [
                'title' => 'Yellow Flame on Your Gas Stove? Here Is How to Fix It',
                'excerpt' => 'A healthy burner burns blue. Yellow tips mean wasted gas and sooty pots — usually fixed with a ten-minute clean. Step-by-step guide.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Stove', 'Maintenance', 'Gas Safety', 'Kitchen'],
                'body' => '<p>A gas flame should burn crisp blue with a whisper. Yellow, lazy flames mean incomplete combustion: wasted gas, blackened pots and more fumes in your kitchen.</p>'
                    . '<h2>The ten-minute fix</h2>'
                    . '<ol>'
                    . '<li>Cool the stove fully and lift off the trivets and burner caps.</li>'
                    . '<li>Brush the burner ports with an old toothbrush; clear blocked holes with a pin — never a toothpick that can snap inside.</li>'
                    . '<li>Wash caps in soapy water, dry <em>completely</em>, and refit seated flat.</li>'
                    . '<li>Still yellow? The air shutter needs adjusting or the regulator pressure is off — that is a technician job.</li>'
                    . '</ol>'
                    . '<h2>Prevention</h2>'
                    . '<p>Wipe spills before they carbonise. Brass-burner stoves like the ' . $link('Shanghai Superior Quality 2-Burner Stainless Steel Tabletop Gas Stove') . ' hold their tune far longer than aluminium ones. If yours is past saving, see ' . $catLink('Stoves', 'stoves') . '.</p>',
            ],
            [
                'title' => 'Geyser Service Season: Prepare Your Water Heater Before the First Cold Snap',
                'excerpt' => 'Sacrificial rods, sediment flushes and thermostat checks — the pre-winter geyser service that prevents mid-December cold showers.',
                'categories' => ['Tips & Maintenance', 'Seasonal Guides'],
                'tags' => ['Geyser', 'Winter', 'Maintenance'],
                'body' => '<p>Every December the same story: the first cold week arrives and every plumber in town is booked. Service your geyser in October and skip the queue.</p>'
                    . '<h2>Storage geysers</h2>'
                    . '<ul>'
                    . '<li><strong>Flush the tank</strong> — a bucket of rusty sediment robs heating efficiency.</li>'
                    . '<li><strong>Check the sacrificial anode rod</strong>; a consumed rod means the tank itself starts corroding.</li>'
                    . '<li><strong>Test the thermostat</strong> at 50–55°C — hot enough for comfort, low enough to save gas and prevent scalding.</li>'
                    . '</ul>'
                    . '<h2>Instant geysers</h2>'
                    . '<p>Tankless units like the ' . $link('Glam Gas Instant Gas Water Heater (Tankless Geyser)') . ' need less ritual: replace the ignition batteries yearly, descale the heat exchanger if flow weakens, and confirm the flame-failure cut-off fires.</p>'
                    . '<p>Heater beyond help? Compare ' . $catLink('Instant Geysers', 'instant-geysers') . ' and ' . $catLink('Electric Geysers', 'electric-geysers') . ' before the rush.</p>',
            ],
            [
                'title' => 'Iron Sticking to Clothes? Restore the Soleplate in Five Minutes',
                'excerpt' => 'Melted polyester, starch build-up and hard-water spots all cure with home remedies. How to keep a dry iron gliding like new.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Iron', 'Cleaning', 'Maintenance', 'Home Appliances'],
                'body' => '<p>An iron that drags or snags can ruin a shalwar kameez in one pass. The fix almost never requires a new iron — just a clean soleplate.</p>'
                    . '<h2>Three home remedies</h2>'
                    . '<ol>'
                    . '<li><strong>Starch haze:</strong> iron over a damp cotton cloth sprinkled with a little salt on low heat.</li>'
                    . '<li><strong>Melted synthetic:</strong> heat the iron slightly, then rub the residue off with a wooden spatula edge — never a knife on a coated plate.</li>'
                    . '<li><strong>General grime:</strong> rub a paste of baking soda and water with a soft cloth on a <em>cold</em> plate, wipe clean, dry fully.</li>'
                    . '</ol>'
                    . '<h2>Daily habits</h2>'
                    . '<p>Match the dial to the fabric — polyester on the cotton setting is how plates get coated in the first place. Non-stick soleplates like the one on the ' . $link('National Inverter Electric Dry Iron NR-17') . ' stay smooth with nothing more than an occasional wipe.</p>'
                    . '<p>Plate scratched beyond saving? A new iron in ' . $catLink('Irons', 'irons') . ' costs less than you think.</p>',
            ],
            [
                'title' => 'Blender Care: Sharp Blades, Fresh Jars and a Motor That Lasts',
                'excerpt' => 'Blunt blades and smelly jars are not inevitable. The 60-second clean and simple habits that keep a blender at full strength.',
                'categories' => ['Tips & Maintenance', 'Kitchen & Cooking'],
                'tags' => ['Blender & Juicer', 'Cleaning', 'Maintenance', 'Kitchen'],
                'body' => '<p>Most blenders die young from two causes: dried-on residue seizing the blade shaft, and overloaded motors. Both are entirely avoidable.</p>'
                    . '<h2>The 60-second clean</h2>'
                    . '<p>Right after pouring your shake, half-fill the jar with warm water and a drop of dish soap, pulse for twenty seconds, rinse. The blade assembly cleans itself — no risky finger-scrubbing around the blades.</p>'
                    . '<h2>Motor rules</h2>'
                    . '<ul>'
                    . '<li>Cut fruit small; add liquid first for thick shakes.</li>'
                    . '<li>Run in 30–40 second bursts, not marathon minutes.</li>'
                    . '<li>Grind truly hard spices in the dry mill attachment — that is what it is for on machines like the ' . $link('National 3-in-1 Juicer, Blender and Dry Miller (Model MJ-176)') . '.</li>'
                    . '</ul>'
                    . '<h2>Odour reset</h2>'
                    . '<p>Blend warm water with lemon slices to lift garlic and masala smells from plastic jars. If your jar has clouded past redemption, see the current ' . $catLink('Blenders & Juicers', 'blenders-juicers') . ' range.</p>',
            ],
            [
                'title' => 'How to Sanitise a Water Dispenser (and Why It Tastes Odd If You Skip It)',
                'excerpt' => 'That faint plastic taste is a cleaning reminder. The simple quarterly sanitising routine every dispenser owner should know.',
                'categories' => ['Tips & Maintenance'],
                'tags' => ['Water Dispenser', 'Cleaning', 'Maintenance', 'Home Appliances'],
                'body' => '<p>A dispenser pours your drinking water, yet it is often the least-cleaned appliance in the house. A quarterly sanitise keeps the taps fresh and the tanks safe.</p>'
                    . '<h2>The quarterly routine</h2>'
                    . '<ol>'
                    . '<li>Unplug and remove the bottle; drain both taps fully.</li>'
                    . '<li>Fill the reservoir with a solution of one tablespoon white vinegar per litre of water; let it sit 10 minutes.</li>'
                    . '<li>Drain through <em>both</em> taps so the lines are treated too.</li>'
                    . '<li>Rinse twice with clean water, wipe the drip tray and bottle collar, reassemble.</li>'
                    . '</ol>'
                    . '<h2>Between cleans</h2>'
                    . '<p>Wipe the taps weekly — they are touched by every hand in the house. Stainless-tank units like the ' . $link('PEL Table-Top Classic 115 Water Dispenser') . ' resist scale and odours better than bare-plastic ones; models with storage like the ' . $link('Choice Appliances Floor-Standing Water Dispenser with Built-In Mini Refrigerator') . ' also need their fridge cabinet wiped monthly.</p>'
                    . '<p>Upgrading? Compare ' . $catLink('Water Dispensers', 'water-dispenser') . '.</p>',
            ],
        ];
    }

    /** Energy saving, solar and load-shedding survival (7 posts). */
    private function energyAndSolarPosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'The Fan Math: What Switching to Hybrid Fans Actually Saves Per Year',
                'excerpt' => 'We do the unit-by-unit arithmetic on replacing 100W fans with 35W hybrids — and how fast the swap pays for itself.',
                'categories' => ['Energy Saving'],
                'tags' => ['Fans', 'Energy Saving', 'Inverter', 'Comparison'],
                'body' => '<p>Fans run more hours than any other appliance in a Pakistani home — often 12 hours a day for 8 months. That makes fan wattage the quietest big number on your bill.</p>'
                    . '<h2>The arithmetic</h2>'
                    . '<p>A conventional fan at ~95W running 12 hours uses about 1.14 units a day. A hybrid inverter fan like the ' . $link('GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)') . ' at ~35W uses 0.42 — a saving of roughly <strong>0.72 units per fan per day</strong>. Across an 8-month season that is ~170 units per fan; multiply by your tariff slab and by every fan in the house, and the fans effectively buy themselves.</p>'
                    . '<h2>The hidden second saving</h2>'
                    . '<p>Lower wattage also shrinks the UPS battery you need for outages — or triples how long the one you own lasts. The ' . $link('PEL DC Inverter Ceiling Fan SmartSaver') . ' delivers the same benefit in a classic design.</p>'
                    . '<p>Start with the rooms you use most — see ' . $catLink('DC Fans', 'dc-fans') . '.</p>',
            ],
            [
                'title' => 'How Many Solar Panels Does Your Home Actually Need?',
                'excerpt' => 'A simple worksheet: read your bill, count your load, and size a rooftop system in 550W panels — no salesman required.',
                'categories' => ['Energy Saving', 'Solar & Backup Power'],
                'tags' => ['Solar Panel', 'Solar', 'Energy Saving', 'Buying Guide'],
                'body' => '<p>Solar quotes make more sense when you arrive knowing your own numbers. Here is the back-of-envelope method installers use.</p>'
                    . '<h2>Step 1: your daily units</h2>'
                    . '<p>Take monthly units from your bill and divide by 30. A typical mid-size home lands between 15 and 25 units a day.</p>'
                    . '<h2>Step 2: panel output</h2>'
                    . '<p>In Pakistani sun, a 550W panel like the ' . $link('Longi Hi-MO 550W Monocrystalline Mono PERC Solar Panel') . ' produces roughly 2.2–2.5 units a day after real-world losses. The ' . $link('Homage Solar Panel 550W Mono PERC') . ' performs in the same band.</p>'
                    . '<h2>Step 3: divide</h2>'
                    . '<p>20 daily units ÷ 2.3 ≈ <strong>9 panels (about a 5 kW system)</strong> to cover daytime load and bank credit for evenings under net metering. Roof space rule: each panel needs just over two square metres.</p>'
                    . '<p>Browse panels and plan your array in ' . $catLink('Solar Plates', 'solar-plates') . '.</p>',
            ],
            [
                'title' => 'Gas vs Electric Cooking: What a Meal Really Costs in 2026',
                'excerpt' => 'LPG at market rates vs electricity per unit — we compare the true cost of cooking daal chawal on gas, infrared and hybrid hobs.',
                'categories' => ['Energy Saving', 'Kitchen & Cooking'],
                'tags' => ['Cooktop', 'LPG', 'Energy Saving', 'Kitchen', 'Comparison'],
                'body' => '<p>With gas pressure unreliable and both fuels repriced every quarter, the cheapest way to cook is no longer obvious. Here is the honest comparison.</p>'
                    . '<h2>The rough numbers</h2>'
                    . '<p>An hour of one gas burner at full flame burns roughly 0.20–0.25 kg of LPG. An infrared cooker like the ' . $link('RAF Multifunction Infrared Cooker & Hot Plate R.8045 (3500W)') . ' at medium power draws about 1.5–2 units in the same hour. At current prices the two are far closer than most people assume — and electric wins outright if you have rooftop solar generating your daytime units.</p>'
                    . '<h2>The resilient kitchen</h2>'
                    . '<p>The real answer is <em>both</em>. A hybrid unit like the ' . $link('Hybrid Gas & Electric Built-In Kitchen Hob') . ' switches fuels as supply dictates, and a tabletop stove plus infrared plate covers the same bases for a fraction of the cost.</p>'
                    . '<p>Explore ' . $catLink('Cooktops', 'cooktops') . ' and ' . $catLink('Built-In Hobs', 'built-in-hobs') . ' to build yours.</p>',
            ],
            [
                'title' => 'Seven Laundry Habits That Quietly Cut Your Electricity Bill',
                'excerpt' => 'Full loads, cold water and sun-drying — small changes to washing routine that add up to real savings every month.',
                'categories' => ['Energy Saving'],
                'tags' => ['Washing Machine', 'Energy Saving', 'Home Appliances'],
                'body' => '<p>Laundry is one of the few chores where better habits cost nothing and save money every single week.</p>'
                    . '<h2>The seven habits</h2>'
                    . '<ol>'
                    . '<li><strong>Wash full loads</strong> — two half loads use nearly double the power and water.</li>'
                    . '<li><strong>Use cold water</strong>; detergents are formulated for it, and heating water is the hidden cost.</li>'
                    . '<li><strong>Time the wash</strong>, do not guess — fifteen minutes rarely cleans better than eight.</li>'
                    . '<li><strong>Sun-dry everything</strong> — Pakistan\'s free dryer works 300 days a year.</li>'
                    . '<li><strong>Pre-soak heavy stains</strong> instead of double-washing.</li>'
                    . '<li><strong>Clean the lint filter</strong>; a clogged machine works harder.</li>'
                    . '<li><strong>Right-size the machine</strong> — an energy-saving model like the ' . $link('Asia Super Wash Single Tub Washing Machine SA-240') . ' matches wash power to load.</li>'
                    . '</ol>'
                    . '<p>Machines with efficiency modes are marked across our ' . $catLink('Washing Machines', 'washing-machine') . ' range.</p>',
            ],
            [
                'title' => 'The 12V Solar Fan: Off-Grid Cooling for Shops, Dhabas and Village Homes',
                'excerpt' => 'No UPS, no inverter — a 12V DC fan wired straight to a panel or battery keeps air moving wherever the grid does not reach.',
                'categories' => ['Solar & Backup Power'],
                'tags' => ['Solar Fan', 'Solar', 'Load Shedding', 'Fans'],
                'body' => '<p>For a roadside shop, a tube-well room or a village baithak, the simplest cooling system in the world is a panel, a wire and a 12V fan — no inverter, no installation, no bill.</p>'
                    . '<h2>Why 12V DC works</h2>'
                    . '<p>A fan like the ' . $link('Sogo 18-Inch 12V DC Solar Pedestal Fan (Rechargeable, AC/DC/Solar)') . ' draws only ~36W, so a single 40–60W panel runs it all day in direct sun, and any 12V battery carries it through the evening. The included adapter also runs it from a wall socket when the grid is up.</p>'
                    . '<h2>Setup in one paragraph</h2>'
                    . '<p>Point the panel south at roughly your latitude angle, connect the battery through a basic charge controller, clip on the fan. That is the whole system — and it scales: add a panel from our ' . $catLink('Solar Plates', 'solar-plates') . ' range and a second fan for bigger spaces.</p>'
                    . '<p>See all off-grid options in ' . $catLink('Solar Fans', 'solar-fans') . '.</p>',
            ],
            [
                'title' => 'The UPS-Friendly Home: Appliances That Sip Backup Power Instead of Draining It',
                'excerpt' => 'Your UPS is only as good as the loads on it. The appliances worth connecting — and the ones that kill batteries in minutes.',
                'categories' => ['Solar & Backup Power', 'Energy Saving'],
                'tags' => ['Load Shedding', 'Inverter', 'Energy Saving', 'Home Appliances'],
                'body' => '<p>Most people buy a bigger UPS when the honest fix is smaller loads. Choose backup-friendly appliances and the battery you already own suddenly lasts the whole outage.</p>'
                    . '<h2>Connect these</h2>'
                    . '<ul>'
                    . '<li><strong>Hybrid fans</strong> (30–40W) — the ' . $link('GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)') . ' even runs directly on 12V DC.</li>'
                    . '<li><strong>LED lights</strong> (5–12W each).</li>'
                    . '<li><strong>Wi-Fi router</strong> (~10W) — work continues.</li>'
                    . '<li><strong>A tower cooler</strong> on low (~120–180W) like the ' . $link('Global High Chill Tower Air Cooler with Ice Box & Remote') . ' for the hottest evenings.</li>'
                    . '</ul>'
                    . '<h2>Never connect these</h2>'
                    . '<p>Irons, geysers, microwaves and heaters — anything that makes heat from electricity drains a battery in minutes. Cook on gas during outages with a tabletop stove from ' . $catLink('Stoves', 'stoves') . ' instead.</p>',
            ],
            [
                'title' => 'The Complete Load-Shedding Survival Kit for a Pakistani Home',
                'excerpt' => 'Fans, light, water, cooking and phone charging — the one-page checklist that keeps a household comfortable through any outage schedule.',
                'categories' => ['Solar & Backup Power'],
                'tags' => ['Load Shedding', 'Solar', 'Home Appliances', 'Buying Guide'],
                'body' => '<p>Outages are a schedule, not a surprise — so a prepared home barely notices them. Here is the complete kit, roughly in order of impact.</p>'
                    . '<h2>The checklist</h2>'
                    . '<ol>'
                    . '<li><strong>Air:</strong> one hybrid fan per occupied room — start with the ' . $link('GFC 56-Inch AC/DC Hybrid Inverter Ceiling Fan (Solar/UPS Ready)') . ' or a portable ' . $link('Sogo 18-Inch 12V DC Solar Pedestal Fan (Rechargeable, AC/DC/Solar)') . '.</li>'
                    . '<li><strong>Power:</strong> a UPS sized to those reduced loads, topped by panels from ' . $catLink('Solar Plates', 'solar-plates') . ' so daytime outages cost nothing.</li>'
                    . '<li><strong>Cooking:</strong> a gas fallback — a ' . $link('Shanghai Superior Quality 2-Burner Stainless Steel Tabletop Gas Stove') . ' with a filled cylinder from ' . $catLink('LPG Cylinders', 'lpg-cylinders') . '.</li>'
                    . '<li><strong>Hot water:</strong> gas, not electric — an instant unit from ' . $catLink('Instant Geysers', 'instant-geysers') . ' works through any outage.</li>'
                    . '<li><strong>Cold water:</strong> fill bottles before scheduled cuts; a dispenser keeps serving from its tank.</li>'
                    . '</ol>'
                    . '<p>Build the kit one item a month and the next loadshedding season will pass unnoticed.</p>',
            ],
        ];
    }

    /** Kitchen setups and cooking-life features (6 posts). */
    private function kitchenPosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'Cooking Through Gas Load-Shedding: The Hybrid Kitchen Playbook',
                'excerpt' => 'When the gas pressure drops at dinner time, a hybrid hob or infrared plate keeps the handi moving. Here is the setup that never stops cooking.',
                'categories' => ['Kitchen & Cooking'],
                'tags' => ['Built-In Hob', 'Cooktop', 'Load Shedding', 'Kitchen'],
                'body' => '<p>Winter gas schedules have turned dinner into a race. The fix is a kitchen that can switch fuels mid-recipe.</p>'
                    . '<h2>Option 1: the hybrid hob</h2>'
                    . '<p>The ' . $link('Hybrid Gas & Electric Built-In Kitchen Hob') . ' pairs two brass gas burners with a 2000W ceramic plate on one 90cm deck. Gas fades — slide the pot half a foot to the electric zone and keep going.</p>'
                    . '<h2>Option 2: the add-on plate</h2>'
                    . '<p>Already own a hob you love? Park a ' . $link('RAF Multifunction Infrared Cooker & Hot Plate R.8045 (3500W)') . ' in the corner as the emergency burner. It works with every pot you own, including the clay handi.</p>'
                    . '<h2>The pressure trick</h2>'
                    . '<p>Low pressure hits hardest at peak hours; cook rice and daal in the afternoon lull, refrigerate, and give them a two-minute reheat at dinner — a microwave from ' . $catLink('Microwave Ovens', 'microwave-ovens') . ' earns its keep here.</p>',
            ],
            [
                'title' => 'The Hob-and-Hood Combo: Designing a Modern Kitchen That Stays Clean',
                'excerpt' => 'Matching a built-in hob with the right chimney is the single best kitchen design decision. Sizes, spacing and pairings that work.',
                'categories' => ['Kitchen & Cooking', 'Buying Guides'],
                'tags' => ['Built-In Hob', 'Kitchen Hood', 'Kitchen', 'Buying Guide'],
                'body' => '<p>A built-in hob without a hood above it is half a renovation — the glass looks stunning for a month, then the grease film arrives. Plan them together.</p>'
                    . '<h2>The pairing rules</h2>'
                    . '<ul>'
                    . '<li><strong>Width:</strong> the hood should match or exceed the hob — a 90cm canopy over a 76cm hob catches the rising plume.</li>'
                    . '<li><strong>Height:</strong> mount 65–75cm above the burners; higher loses suction, lower crowds the cook.</li>'
                    . '<li><strong>Duct out, not around:</strong> the shortest straight duct to an outside wall beats any recirculating filter.</li>'
                    . '</ul>'
                    . '<h2>Pairings we recommend</h2>'
                    . '<p>Golden statement kitchen: the ' . $link('Choice Appliances Premium Tri-Series 3-Burner Built-In Gas Hob (GL 308 DG BR Golden)') . ' under the ' . $link('Choice Appliances Premium Curved Glass Kitchen Range Hood (Chimney)') . '. Heavy-duty daily cooking: the ' . $link('Ideal Appliances Premium 3-Burner Built-In Stainless Steel Gas Hob') . ' under the auto-clean ' . $link('Glam Gas Wave Series Smart Kitchen Range Hood (Chimney)') . '.</p>'
                    . '<p>Start with ' . $catLink('Built-In Hobs', 'built-in-hobs') . ' and ' . $catLink('Kitchen Hoods', 'kitchen-hoods') . '.</p>',
            ],
            [
                'title' => 'Feeding the Whole Khandaan: Why Big Families Swear by 5-Burner Ranges',
                'excerpt' => 'Five pots at once, a double oven below and a rotisserie for good measure — how a full-size cooking range transforms dawat season.',
                'categories' => ['Kitchen & Cooking', 'Seasonal Guides'],
                'tags' => ['Cooking Range', 'Kitchen', 'Home Appliances'],
                'body' => '<p>Anyone who has cooked a dawat on three burners knows the bottleneck: the biryani holds one, the qorma the second, and everything else queues for the third.</p>'
                    . '<h2>What five burners change</h2>'
                    . '<p>On the ' . $link('National Premium 5-Burner Double-Door Cooking Range with Oven & Grill') . ', the rice, two salans, chai and tarka each get their own flame. Below, the double-door oven roasts while the grill crisps — the rotisserie handles a whole chicken untouched.</p>'
                    . '<h2>Built for the marathon</h2>'
                    . '<p>A full stainless body wipes down after the heaviest session, and auto-ignition means no hunting for the lighter with oily hands. For smaller households, the ' . $link('National Free-Standing 3-Burner Cooking Range with Oven & Grill') . ' offers the same oven convenience in a tighter footprint.</p>'
                    . '<p>Both are in ' . $catLink('Cooking Ranges', 'cooking-ranges') . ' — measure your kitchen gap before ordering.</p>',
            ],
            [
                'title' => 'The Small-Kitchen Setup: Full Cooking Power in Six Feet of Counter',
                'excerpt' => 'Apartment, hostel or annexe — a single-burner hob, a tabletop stove and one smart plate cover everything a compact kitchen needs.',
                'categories' => ['Kitchen & Cooking'],
                'tags' => ['Kitchen', 'Stove', 'Cooktop', 'Buying Guide'],
                'body' => '<p>A small kitchen is not a lesser kitchen — it just refuses to carry dead weight. Three appliances, chosen well, cover every meal.</p>'
                    . '<h2>The trio</h2>'
                    . '<ol>'
                    . '<li><strong>The fitted burner:</strong> a ' . $link('Choice Appliances Single-Burner Built-In Gas Hob') . ' set into the counter keeps the daily chai and omelette station permanent and easy to wipe.</li>'
                    . '<li><strong>The workhorse:</strong> a ' . $link('Shanghai Single-Burner Stainless Steel Tabletop Gas Stove') . ' handles the main cooking and stows in a cupboard when guests arrive.</li>'
                    . '<li><strong>The electric fallback:</strong> the ' . $link('RAF Multifunction Infrared Cooker & Hot Plate R.8045 (3500W)') . ' cooks when gas will not, and doubles as a table-side hotplate.</li>'
                    . '</ol>'
                    . '<h2>Space discipline</h2>'
                    . '<p>Everything else — kettle duty, reheating, defrosting — folds into one 20-litre microwave from ' . $catLink('Microwave Ovens', 'microwave-ovens') . '. Four appliances, six feet of counter, zero compromises.</p>',
            ],
            [
                'title' => 'From Wedding Shakes to Winter Soups: Getting the Most From Your Blender',
                'excerpt' => 'One 1000W base, three attachments, a whole season of menus. Practical blender recipes and the settings that make them work.',
                'categories' => ['Kitchen & Cooking'],
                'tags' => ['Blender & Juicer', 'Kitchen', 'Home Appliances'],
                'body' => '<p>Most blenders spend their lives making the same two shakes. The machine can do far more — if you use the right attachment at the right speed.</p>'
                    . '<h2>Season by season</h2>'
                    . '<ul>'
                    . '<li><strong>Summer:</strong> mango shakes on speed 1 (chunky) or 2 (smooth); the juicer attachment on a ' . $link('National 3-in-1 Juicer, Blender and Dry Miller (Model MJ-176)') . ' turns crate-season mangoes into clear juice.</li>'
                    . '<li><strong>Wedding season:</strong> pre-crush ice in short pulses, then blend — never long continuous runs.</li>'
                    . '<li><strong>Winter:</strong> blend boiled vegetables directly into smooth soups; the ' . $link('Kenwood 2-in-1 Blender and Grinder Mill (Model KW-871)') . ' handles hot liquids confidently with the lid vented.</li>'
                    . '<li><strong>All year:</strong> the dry mill grinds garam masala, dried chillies and coffee — a fresher pantry for free.</li>'
                    . '</ul>'
                    . '<p>The ' . $link('Panasonic Classic 2-in-1 Blender and Dry Grinder Mill (Model HJ-661)') . ' covers the same ground at a friendlier price. All three live in ' . $catLink('Blenders & Juicers', 'blenders-juicers') . '.</p>',
            ],
            [
                'title' => 'Instant Hot Water in the Kitchen: Small Geysers That Punch Above Their Size',
                'excerpt' => 'Greasy dishes need hot water on demand, not a tank upstairs. Compact instant geysers compared for kitchen duty.',
                'categories' => ['Kitchen & Cooking', 'Buying Guides'],
                'tags' => ['Geyser', 'Kitchen', 'Winter', 'Buying Guide'],
                'body' => '<p>The kitchen tap is where instant geysers make the most sense: short bursts of hot water, many times a day, with zero standby loss.</p>'
                    . '<h2>Why instant wins here</h2>'
                    . '<p>A storage geyser reheats its whole tank for every sink of dishes. A tankless unit fires only while the tap runs — the ' . $link('Glam Gas Instant Gas Water Heater (Tankless Geyser)') . ' lights automatically on flow and delivers up to 10 litres a minute, plenty for a double sink.</p>'
                    . '<h2>Sizing it right</h2>'
                    . '<p>For a kitchen alone, the compact ' . $link('Boss Instant Gas Geyser 6L') . ' is ample and mounts neatly above the sink. Serving a bathroom on the same line too? Step up to the higher-flow Glam Gas and set the temperature dial once for both.</p>'
                    . '<p>Winter dish duty gets dramatically easier — see all ' . $catLink('Instant Geysers', 'instant-geysers') . '.</p>',
            ],
        ];
    }

    /** Seasonal checklists, safety guides and store news (5 posts). */
    private function seasonalSafetyNewsPosts(\Closure $link, \Closure $catLink): array
    {
        return [
            [
                'title' => 'The October Checklist: Get Your Home Winter-Ready Before Prices Peak',
                'excerpt' => 'Geysers, heaters and irons all cost more in December. The early-bird checklist that beats both the rush and the price hikes.',
                'categories' => ['Seasonal Guides'],
                'tags' => ['Winter', 'Geyser', 'Patio Heater', 'Buying Guide'],
                'body' => '<p>Winter appliances follow the same curve every year: cheap and in stock through October, expensive and back-ordered by mid-December. Shop the checklist early.</p>'
                    . '<h2>Inside the house</h2>'
                    . '<ul>'
                    . '<li><strong>Hot water first:</strong> service the geyser now, or replace it — compare ' . $catLink('Geysers', 'geysers') . ' while every model is in stock.</li>'
                    . '<li><strong>The pressing station:</strong> school uniforms meet foggy mornings; a reliable ' . $link('National Inverter Electric Dry Iron NR-17') . ' clears the pile fast.</li>'
                    . '<li><strong>Warm drinks on tap:</strong> a dispenser\'s hot tap — like the ' . $link('PEL Table-Top Classic 115 Water Dispenser') . ' — makes chai rounds and honey-lemon water effortless.</li>'
                    . '</ul>'
                    . '<h2>Outside</h2>'
                    . '<p>Lawn and rooftop season is winter in Pakistan. A heater from ' . $catLink('Patio Heaters', 'patio-heaters') . ' turns the garden into the best room of the house — and a spare filled cylinder from ' . $catLink('LPG Cylinders', 'lpg-cylinders') . ' keeps it burning through the gas cuts.</p>',
            ],
            [
                'title' => 'Beat the First Heatwave: A March Preparation Guide for Pakistani Summers',
                'excerpt' => 'The first 40°C day should not be the day you discover the cooler needs pads. A room-by-room summer prep plan for March.',
                'categories' => ['Seasonal Guides'],
                'tags' => ['Summer', 'Air Cooler', 'Fans', 'Maintenance'],
                'body' => '<p>Summer in Pakistan does not arrive gently — it slams the door open one March afternoon. Homes that prepped in February coast through it.</p>'
                    . '<h2>Revive the cooling fleet</h2>'
                    . '<ul>'
                    . '<li><strong>Coolers:</strong> replace tired honeycomb pads, scrub the tank, test the pump. Pads a season past their prime cool half as well.</li>'
                    . '<li><strong>Fans:</strong> tighten blade screws and oil bearings; a wobble in March is a failure in June.</li>'
                    . '<li><strong>Upgrades:</strong> if last year was the cooler\'s last, the ' . $link('Global High Chill Tower Air Cooler with Ice Box & Remote') . ' and the classic ' . $link('Super Asia Room Air Cooler ECM-4000') . ' are both strong starts — see ' . $catLink('Coolers', 'coolers') . '.</li>'
                    . '</ul>'
                    . '<h2>Plan for the outages</h2>'
                    . '<p>Summer load-shedding is guaranteed; comfort during it is optional. Swap at least the bedroom fan for a hybrid from ' . $catLink('DC Fans', 'dc-fans') . ' and it will run on the UPS all night.</p>',
            ],
            [
                'title' => 'LPG Safety at Home: The Ten Rules Every Household Should Follow',
                'excerpt' => 'Cylinders, regulators and pipes are safe when treated right. Ten non-negotiable rules for every home that cooks on LPG.',
                'categories' => ['Appliance Safety'],
                'tags' => ['LPG', 'Gas Safety', 'Home Appliances'],
                'body' => '<p>LPG runs millions of Pakistani kitchens safely every day — the accidents that make the news almost always trace back to the same few shortcuts. Here are the rules that prevent them.</p>'
                    . '<h2>The ten rules</h2>'
                    . '<ol>'
                    . '<li>Store cylinders <strong>upright</strong>, in ventilated space, never in a sealed cupboard or below ground level.</li>'
                    . '<li>Use a proper regulator — a quality unit like the ' . $link('Super Gree Clip-On Low-Pressure Gas Regulator') . ' holds steady pressure and clips off cleanly.</li>'
                    . '<li>Replace rubber hose pipes every two years; check for cracks monthly.</li>'
                    . '<li>Test new connections with <strong>soapy water</strong>, never a match.</li>'
                    . '<li>Smell gas? Open windows first — touch no switches, light nothing.</li>'
                    . '<li>Close the cylinder valve every night and before travel.</li>'
                    . '<li>Never refill from unauthorised decanting shops.</li>'
                    . '<li>Keep cylinders away from direct sun and heat sources.</li>'
                    . '<li>Retire rusted or dented cylinders — or switch to the explosion-free ' . $link('Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)') . '.</li>'
                    . '<li>Teach every adult in the house where the valve is and how to close it.</li>'
                    . '</ol>'
                    . '<p>Safe equipment starts at ' . $catLink('Gas Appliances', 'gas-appliances') . '.</p>',
            ],
            [
                'title' => 'Gas Geysers and Carbon Monoxide: The Winter Safety Guide That Saves Lives',
                'excerpt' => 'Every winter brings avoidable CO tragedies from bathroom gas heaters. Ventilation rules and the safer setups every family should know.',
                'categories' => ['Appliance Safety', 'Seasonal Guides'],
                'tags' => ['Geyser', 'Gas Safety', 'Winter'],
                'body' => '<p>Carbon monoxide is odourless, invisible and produced by any gas flame short of air. In winter, with bathrooms sealed against the cold, it becomes the season\'s most preventable danger.</p>'
                    . '<h2>The non-negotiables</h2>'
                    . '<ul>'
                    . '<li><strong>Never install an instant geyser inside the bathroom.</strong> Mount it outside the door or on an external wall, with the flue venting outdoors.</li>'
                    . '<li><strong>Keep a permanent air gap</strong> — a ventilator brick or gap under the door. If the flame burns yellow, stop and ventilate.</li>'
                    . '<li><strong>Prefer flame-failure protection:</strong> modern units like the ' . $link('Glam Gas Instant Gas Water Heater (Tankless Geyser)') . ' cut the gas the instant the flame dies.</li>'
                    . '<li><strong>Storage geysers belong outdoors</strong> or in ventilated service spaces — like the ' . $link('Super Asia Gas Geyser 35 Gallon') . ' on its traditional outside wall.</li>'
                    . '</ul>'
                    . '<h2>Know the symptoms</h2>'
                    . '<p>Headache, dizziness and nausea during a hot shower are a CO warning, not the steam. Get out, ventilate, and have the installation inspected before the next use. Safer models across ' . $catLink('Geysers', 'geysers') . '.</p>',
            ],
            [
                'title' => 'New In Store: Kitchen Hobs, Patio Heaters, Composite Cylinders and More',
                'excerpt' => 'The catalogue just grew — built-in hobs, chimneys, cooking ranges, solar fans and the season\'s most requested outdoor heaters, all in one drop.',
                'categories' => ['News & Offers'],
                'tags' => ['New Arrivals', 'Kitchen', 'Patio Heater', 'Home Appliances'],
                'body' => '<p>Our biggest catalogue expansion yet is live — dozens of new appliances across the kitchen, laundry, outdoor and solar ranges. The highlights:</p>'
                    . '<h2>For the kitchen</h2>'
                    . '<p>Built-in gas hobs from Choice and Ideal — including the golden-glass ' . $link('Choice Appliances Premium Tri-Series 3-Burner Built-In Gas Hob (GL 308 DG BR Golden)') . ' — plus auto-clean chimneys, National cooking ranges and a full ' . $catLink('Blenders & Juicers', 'blenders-juicers') . ' line-up.</p>'
                    . '<h2>For the season</h2>'
                    . '<p>Three styles of outdoor heater land in ' . $catLink('Patio Heaters', 'patio-heaters') . ' just as lawn-dinner season begins, alongside the featherweight ' . $link('Burhan Gas Company (BGC) Composite Fiber LPG Cylinder (10 kg)') . ' to fuel them.</p>'
                    . '<h2>For the bills</h2>'
                    . '<p>Tier-1 ' . $link('Longi Hi-MO 550W Monocrystalline Mono PERC Solar Panel') . ' panels, hybrid ceiling fans and 12V solar pedestal fans round out the ' . $catLink('Solar Plates', 'solar-plates') . ' and ' . $catLink('Solar Fans', 'solar-fans') . ' shelves.</p>'
                    . '<p>Every product page carries full researched specifications — browse the ' . $catLink('whole catalogue', 'electronics') . ' and see what fits your home.</p>',
            ],
        ];
    }
}
