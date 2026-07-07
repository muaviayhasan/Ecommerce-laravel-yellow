<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

/**
 * Seeds the storefront home hero carousel with three home-appliance slides
 * (Water Cooler, Washing Machine, Air Cooler). Images are intentionally left
 * blank — set them per slide in Admin → Ecommerce → Hero Slides. Each slide's
 * button links to the matching category on the shop page.
 *
 * Idempotent: matches on `sort_order` so the three demo slides are refreshed
 * in place (no duplicates) rather than appended.
 */
class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'kicker' => 'Beat the summer heat',
                'line1' => 'ELECTRIC',
                'line2' => 'WATER COOLERS',
                'tail' => 'STARTING AT',
                'highlight' => 'Rs 74,999',
                'cta_label' => 'Shop Water Coolers',
                'cta_url' => '/shop?category=water-cooler',
                'image_alt' => 'Electric water cooler',
            ],
            [
                'kicker' => 'Laundry made effortless',
                'line1' => 'AUTOMATIC',
                'line2' => 'WASHING MACHINES',
                'tail' => 'SAVE UP TO',
                'highlight' => '20% OFF',
                'cta_label' => 'Shop Washing Machines',
                'cta_url' => '/shop?category=washing-machine',
                'image_alt' => 'Automatic washing machine',
            ],
            [
                'kicker' => 'Cool every room',
                'line1' => 'ROOM',
                'line2' => 'AIR COOLERS',
                'tail' => 'FROM',
                'highlight' => 'Rs 28,999',
                'cta_label' => 'Shop Air Coolers',
                'cta_url' => '/shop?category=air-cooler',
                'image_alt' => 'Room air cooler',
            ],
        ];

        foreach ($slides as $i => $slide) {
            HeroSlide::updateOrCreate(
                ['sort_order' => $i],
                array_merge($slide, [
                    // Images added later via the admin — clear any previous demo image.
                    'image_media_id' => null,
                    'image_path' => null,
                    'sort_order' => $i,
                    'is_active' => true,
                ]),
            );
        }
    }
}
