<?php

namespace Database\Seeders;

use App\Models\PromoCard;
use Illuminate\Database\Seeder;

/**
 * Seeds the home-page promo grid with the cards that used to be hardcoded in
 * resources/views/storefront/home.blade.php, so the grid keeps its look while
 * becoming editable from Admin → Ecommerce → Promo Cards. Idempotent (by title).
 */
class PromoCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'kicker' => 'Catch the hottest', 'title' => 'Deals', 'subtitle' => 'In Cameras',
                'display_type' => PromoCard::TYPE_SHOP,
                'image_path' => '/images/promos/promo-1.png', 'image_alt' => 'Deals in cameras',
            ],
            [
                'kicker' => 'The New', 'title' => '360 Cameras', 'subtitle' => null,
                'display_type' => PromoCard::TYPE_PRICE, 'prefix' => 'From', 'currency' => '$', 'amount' => '749', 'cents' => '99',
                'image_path' => '/images/promos/promo-2.png', 'image_alt' => '360 camera',
            ],
            [
                'kicker' => 'Tablets, Smartphones', 'title' => 'And More', 'subtitle' => null,
                'display_type' => PromoCard::TYPE_PERCENT, 'prefix' => 'Up to', 'amount' => '70',
                'image_path' => '/images/promos/promo-3.png', 'image_alt' => 'Tablets and smartphones',
            ],
            [
                'kicker' => 'The New', 'title' => '360 Cameras', 'subtitle' => null,
                'display_type' => PromoCard::TYPE_PERCENT, 'prefix' => 'Up to', 'amount' => '70',
                'image_path' => '/images/promos/promo-4.png', 'image_alt' => '360 camera',
            ],
        ];

        foreach ($cards as $i => $card) {
            // Title alone isn't unique (two "360 Cameras"), so key on title + sort slot.
            PromoCard::updateOrCreate(
                ['title' => $card['title'], 'sort_order' => $i],
                array_merge($card, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
