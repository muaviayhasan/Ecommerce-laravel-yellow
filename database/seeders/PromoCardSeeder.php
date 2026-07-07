<?php

namespace Database\Seeders;

use App\Models\PromoCard;
use Illuminate\Database\Seeder;

/**
 * Seeds the home-page promo grid — the "sales deals" strip immediately below the
 * hero slider — with home-appliance deals priced in Rs. Images are left blank;
 * set them per card in Admin → Ecommerce → Promo Cards. Each card links to the
 * matching category on the shop page.
 *
 * Idempotent: matches on `sort_order` so the four demo cards are refreshed in
 * place (no duplicates).
 */
class PromoCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'kicker' => 'Catch the hottest', 'title' => 'Deals', 'subtitle' => 'In Air Coolers',
                'display_type' => PromoCard::TYPE_SHOP,
                'url' => '/shop?category=air-cooler', 'image_alt' => 'Air cooler deals',
            ],
            [
                'kicker' => 'The New', 'title' => 'Instant Geysers',
                'display_type' => PromoCard::TYPE_PRICE, 'prefix' => 'From', 'currency' => 'Rs ', 'amount' => '15,999',
                'url' => '/shop?category=geysers', 'image_alt' => 'Instant geyser',
            ],
            [
                'kicker' => 'Washing Machines', 'title' => 'And More',
                'display_type' => PromoCard::TYPE_PERCENT, 'prefix' => 'Up to', 'amount' => '20',
                'url' => '/shop?category=washing-machine', 'image_alt' => 'Washing machine deals',
            ],
            [
                'kicker' => 'Go green & save', 'title' => 'Solar Plates',
                'display_type' => PromoCard::TYPE_PRICE, 'prefix' => 'From', 'currency' => 'Rs ', 'amount' => '21,999',
                'url' => '/shop?category=solar-plates', 'image_alt' => 'Solar panel deals',
            ],
        ];

        // Per-card defaults so fields a card omits (e.g. currency on a "shop" card)
        // are reset cleanly rather than carrying over stale values.
        $defaults = [
            'kicker' => null, 'subtitle' => null, 'prefix' => null, 'currency' => null,
            'amount' => null, 'cents' => null, 'image_media_id' => null, 'image_path' => null, 'image_alt' => null,
        ];

        foreach ($cards as $i => $card) {
            PromoCard::updateOrCreate(
                ['sort_order' => $i],
                array_merge($defaults, $card, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
