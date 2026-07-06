<?php

namespace Database\Seeders;

use App\Models\InfoBarItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the home-page info bar (icon + title + subtitle strip) with the items
 * that used to be hardcoded in resources/views/storefront/home.blade.php, so it
 * keeps its look while becoming editable from Admin → Ecommerce → Info Bar.
 * Idempotent (by title).
 */
class InfoBarItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['icon' => 'local_shipping', 'title' => 'Free Delivery', 'subtitle' => 'from Rs 5,000'],
            ['icon' => 'thumb_up', 'title' => '99% Positive', 'subtitle' => 'Feedbacks'],
            ['icon' => 'cached', 'title' => '365 days', 'subtitle' => 'for free return'],
            ['icon' => 'account_balance_wallet', 'title' => 'Payment', 'subtitle' => 'Secure System'],
            ['icon' => 'sell', 'title' => 'Only Best', 'subtitle' => 'Brands'],
        ];

        foreach ($items as $i => $item) {
            InfoBarItem::updateOrCreate(
                ['title' => $item['title']],
                array_merge($item, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
