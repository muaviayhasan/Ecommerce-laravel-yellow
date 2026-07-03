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
            'default_meta_description' => ['value' => '', 'type' => 'string'],
            'default_og_image' => ['value' => '', 'type' => 'string'],
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
            'support_email' => ['value' => '', 'type' => 'string'],
            'business_hours' => ['value' => '', 'type' => 'string'],
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
