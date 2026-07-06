<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

/**
 * Seeds the storefront home hero carousel with the slides that used to be
 * hardcoded in resources/views/storefront/home.blade.php, so the banner keeps
 * its current look while now being editable from Admin → Ecommerce → Hero Slides.
 * Idempotent (updateOrCreate by line1). Images use the static-path fallback;
 * admins can swap in library media per slide.
 */
class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'kicker' => 'Power meets portability',
                'line1' => 'NEXT-GEN LAPTOPS',
                'line2' => 'BUILT FOR SPEED',
                'tail' => 'SAVE UP TO',
                'highlight' => '30% OFF',
                'cta_label' => 'Shop Laptops',
                'cta_url' => null,
                'image_path' => '/assets/images/banner-laptops.png',
                'image_alt' => 'Next-gen laptop',
            ],
            [
                'kicker' => 'Capture every moment',
                'line1' => 'PRO-GRADE',
                'line2' => 'CAMERAS',
                'tail' => 'UP TO',
                'highlight' => '40% OFF',
                'cta_label' => 'Shop Cameras',
                'cta_url' => null,
                'image_path' => '/images/promos/promo-1.png',
                'image_alt' => '4K camera',
            ],
            [
                'kicker' => 'Hear every detail',
                'line1' => 'IMMERSIVE AUDIO',
                'line2' => 'WIRELESS FREEDOM',
                'tail' => 'STARTING AT',
                'highlight' => 'Rs 4,999',
                'cta_label' => 'Shop Audio',
                'cta_url' => null,
                'image_path' => '/assets/images/banner-smartg3.png',
                'image_alt' => 'Wireless audio device',
            ],
        ];

        foreach ($slides as $i => $slide) {
            HeroSlide::updateOrCreate(
                ['line1' => $slide['line1']],
                array_merge($slide, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
