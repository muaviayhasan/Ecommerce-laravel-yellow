<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Default admin-managed settings (PROJECT_DOCUMENTATION §13). Stored in the
     * key-value `settings` table and read everywhere via the setting() helper.
     * `type` drives decoding; `encrypted` secrets are seeded blank.
     *
     * @var array<string, array<string, array{value: mixed, type: string}>>
     */
    private array $defaults = [
        'general' => [
            'app_name' => ['value' => 'Usman Ecommerce', 'type' => 'string'],
            'currency' => ['value' => 'PKR', 'type' => 'string'],
            'currency_symbol' => ['value' => 'Rs', 'type' => 'string'],
            'currency_position' => ['value' => 'left', 'type' => 'string'],
            'decimals' => ['value' => 2, 'type' => 'int'],
            'thousands_separator' => ['value' => ',', 'type' => 'string'],
            'decimal_separator' => ['value' => '.', 'type' => 'string'],
            'timezone' => ['value' => 'Asia/Karachi', 'type' => 'string'],
            'locale' => ['value' => 'en', 'type' => 'string'],
            'date_format' => ['value' => 'd M Y', 'type' => 'string'],
            'time_format' => ['value' => 'h:i A', 'type' => 'string'],
            'items_per_page' => ['value' => 15, 'type' => 'int'],
            'theme' => ['value' => 'light', 'type' => 'string'],
        ],
        'seo' => [
            'title_suffix' => ['value' => 'Usman Ecommerce', 'type' => 'string'],
            /*
             | These were seeded as `default_meta_description` / `default_og_image`,
             | but the admin form (SettingsController "Search engines" / "Social
             | sharing") and storefront/partials/seo.blade.php both read
             | `meta_description` / `og_image`. The seeded values were dead keys that
             | nothing ever read — which is why og:image was empty site-wide and every
             | shared link rendered without a preview image.
             |
             | The old rows are left in place rather than deleted; they are inert, and
             | removing settings rows is not something a seeder should do silently.
             */
            'meta_description' => [
                'value' => 'Home appliances & electronics in Lahore — coolers, geysers, fans, washing machines & solar. Genuine brands, cash on delivery across Pakistan.',
                'type' => 'string',
            ],
            // Deliberately blank: Google dropped meta keywords as a ranking signal in
            // 2009, and nothing in this codebase queries the column — it is written by
            // the admin form and read only to emit the tag.
            'meta_keywords' => ['value' => '', 'type' => 'string'],
            // Point this at a 1200x630 image in Admin → Settings → SEO → Social sharing.
            // Until then seo.blade.php falls back to the logo, which shares but is the
            // wrong shape for a social card.
            'og_image' => ['value' => '', 'type' => 'string'],
            'google_analytics_id' => ['value' => '', 'type' => 'string'],
            'google_site_verification' => ['value' => '', 'type' => 'string'],
            'social_links' => ['value' => [], 'type' => 'json'],
            'organization_name' => ['value' => 'Usman Ecommerce', 'type' => 'string'],
        ],
        'payment' => [
            'cod_enabled' => ['value' => true, 'type' => 'bool'],
            'qr_enabled' => ['value' => false, 'type' => 'bool'],
            'jazzcash_enabled' => ['value' => false, 'type' => 'bool'],
            'jazzcash_merchant_id' => ['value' => '', 'type' => 'encrypted'],
            'easypaisa_enabled' => ['value' => false, 'type' => 'bool'],
            'easypaisa_store_id' => ['value' => '', 'type' => 'encrypted'],
        ],
        'shipping' => [
            'flat_rate' => ['value' => 200, 'type' => 'int'],
            'free_over' => ['value' => 5000, 'type' => 'int'],
            // Returns default to OFF. These values are published to Google as a
            // policy the store is held to, so nothing is claimed until someone
            // turns it on in Admin → Settings → Shipping and enters the real terms.
            'returns_enabled' => ['value' => false, 'type' => 'bool'],
            'returns_days' => ['value' => 7, 'type' => 'int'],
            'returns_method' => ['value' => 'store', 'type' => 'string'],
            'returns_fees' => ['value' => 'customer', 'type' => 'string'],
        ],
        'tax' => [
            'enabled' => ['value' => true, 'type' => 'bool'],
            'rate' => ['value' => 0, 'type' => 'int'],
            'inclusive' => ['value' => false, 'type' => 'bool'],
        ],
        'social_login' => [
            // Credentials are entered from the admin Settings → Social login screen.
            'google_enabled' => ['value' => false, 'type' => 'bool'],
            'facebook_enabled' => ['value' => false, 'type' => 'bool'],
        ],
        'store' => [
            'address' => ['value' => '', 'type' => 'string'],
            'phone' => ['value' => '', 'type' => 'string'],
            'whatsapp' => ['value' => '', 'type' => 'string'],
            'support_email' => ['value' => '', 'type' => 'string'],
            'business_hours' => ['value' => '', 'type' => 'string'],
            // Structured location for LocalBusiness schema. All blank by default —
            // the markup is only emitted once a city is filled in, so an unconfigured
            // install never publishes a half-built address to Google.
            'city' => ['value' => '', 'type' => 'string'],
            'region' => ['value' => '', 'type' => 'string'],
            'postal_code' => ['value' => '', 'type' => 'string'],
            'country' => ['value' => 'PK', 'type' => 'string'],
            'latitude' => ['value' => '', 'type' => 'string'],
            'longitude' => ['value' => '', 'type' => 'string'],
            'opening_days' => ['value' => '', 'type' => 'string'],
            'opens' => ['value' => '', 'type' => 'string'],
            'closes' => ['value' => '', 'type' => 'string'],
            'bill_type' => ['value' => 'a4', 'type' => 'string'],     // a4|thermal — printed order bill format
            'invoice_footer' => ['value' => '', 'type' => 'string'],
        ],
        'mail' => [
            'from_address' => ['value' => 'hello@example.com', 'type' => 'string'],
            'from_name' => ['value' => 'Usman Ecommerce', 'type' => 'string'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $group => $keys) {
            foreach ($keys as $key => $config) {
                $value = $config['type'] === 'json'
                    ? json_encode($config['value'])
                    : (is_bool($config['value']) ? ($config['value'] ? '1' : '0') : (string) $config['value']);

                Setting::firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value, 'type' => $config['type']],
                );
            }
        }
    }
}
