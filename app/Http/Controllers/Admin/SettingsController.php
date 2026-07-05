<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin Settings (CONVENTIONS §6). One show/update pair, parameterised by group;
 * every group/section/field is declared in schema() and rendered with the
 * x-settings.* components. Reads/writes the key-value `settings` table via the
 * Setting model. `settings.view` gates reads, `settings.edit` gates writes.
 */
class SettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['show']),
            new Middleware('can:settings.edit', only: ['update']),
        ];
    }

    public function show(string $group = 'general'): View
    {
        $schema = $this->schema();
        abort_unless(isset($schema[$group]), 404);

        return view('admin.settings.show', [
            'tabs' => collect($schema)->map(fn ($g, $key) => [
                'key' => $key,
                'label' => $g['label'],
                'icon' => $g['icon'],
            ])->values()->all(),
            'group' => $group,
            'config' => $schema[$group],
            'values' => Setting::groupWithDefaults($group, $this->defaults($schema[$group])),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $schema = $this->schema();
        abort_unless(isset($schema[$group]), 404);

        $fields = $this->fields($schema[$group]);

        // Validate only fields that declare rules (toggles are resolved separately).
        $rules = collect($fields)
            ->reject(fn ($meta) => ($meta['input'] ?? '') === 'toggle')
            ->map(fn ($meta) => $meta['rules'] ?? ['nullable'])
            ->all();

        $validated = $request->validate($rules);

        foreach ($fields as $key => $meta) {
            // Checkbox toggles are absent-when-off → resolve to a real boolean.
            if (($meta['input'] ?? '') === 'toggle') {
                $validated[$key] = $request->boolean($key);
                continue;
            }

            // Secrets: a blank submission means "keep the stored value" (§4.3).
            if (($meta['type'] ?? '') === 'encrypted' && blank($request->input($key))) {
                unset($validated[$key]);
            }
        }

        Setting::putGroup(
            $group,
            $validated,
            collect($fields)->map(fn ($meta) => $meta['type'] ?? 'string')->all(),
        );

        return back()->with('settings_status', $schema[$group]['label'] . ' settings saved.');
    }

    /** Flatten a group's sections into key => field-meta. */
    private function fields(array $groupSchema): array
    {
        return collect($groupSchema['sections'])
            ->flatMap(fn ($section) => $section['fields'])
            ->all();
    }

    /** key => default value, for groupWithDefaults(). */
    private function defaults(array $groupSchema): array
    {
        return collect($this->fields($groupSchema))
            ->map(fn ($meta) => $meta['default'] ?? null)
            ->all();
    }

    /**
     * The full settings schema: group → label/icon → sections → fields.
     * Field meta: type (storage), input (UI control), rules, options, help, max,
     * default, rows. Keys mirror SettingsSeeder so values round-trip.
     */
    private function schema(): array
    {
        $timezones = collect(timezone_identifiers_list())->mapWithKeys(fn ($t) => [$t => $t])->all();

        // Date/time format choices — label each with a live example of today's date/time.
        $now = now();
        $dateOptions = collect(['d M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'D, d M Y', 'jS F Y', 'd.m.Y'])
            ->mapWithKeys(fn ($f) => [$f => $now->format($f) . '  ·  ' . $f])->all();
        $timeOptions = collect(['h:i A', 'g:i A', 'H:i', 'h:i:s A', 'H:i:s'])
            ->mapWithKeys(fn ($f) => [$f => $now->format($f) . '  ·  ' . $f])->all();

        return [
            'general' => [
                'label' => 'General',
                'icon' => 'tune',
                'sections' => [
                    [
                        'title' => 'Store identity',
                        'fields' => [
                            'app_name' => ['input' => 'text', 'label' => 'Store name', 'max' => 255, 'rules' => ['required', 'string', 'max:255']],
                            'theme' => ['input' => 'select', 'label' => 'Default theme', 'options' => ['light' => 'Light', 'dark' => 'Dark'], 'rules' => ['required', 'in:light,dark']],
                        ],
                    ],
                    [
                        'title' => 'Localization & currency',
                        'description' => 'How money, dates and numbers are formatted across the store and admin.',
                        'fields' => [
                            'currency' => ['input' => 'text', 'label' => 'Currency code', 'max' => 3, 'rules' => ['required', 'string', 'max:3'], 'help' => 'ISO code, e.g. PKR'],
                            'currency_symbol' => ['input' => 'text', 'label' => 'Currency symbol', 'max' => 8, 'rules' => ['required', 'string', 'max:8']],
                            'currency_position' => ['input' => 'select', 'label' => 'Symbol position', 'options' => ['left' => 'Left — Rs 100', 'right' => 'Right — 100 Rs'], 'rules' => ['required', 'in:left,right']],
                            'decimals' => ['type' => 'int', 'input' => 'number', 'label' => 'Decimal places', 'rules' => ['required', 'integer', 'min:0', 'max:4']],
                            'thousands_separator' => ['input' => 'text', 'label' => 'Thousands separator', 'max' => 1, 'rules' => ['nullable', 'string', 'max:1']],
                            'decimal_separator' => ['input' => 'text', 'label' => 'Decimal separator', 'max' => 1, 'rules' => ['required', 'string', 'max:1']],
                            'timezone' => ['input' => 'select', 'label' => 'Timezone', 'options' => $timezones, 'rules' => ['required', 'timezone']],
                            'locale' => ['input' => 'select', 'label' => 'Locale', 'options' => ['en' => 'English', 'ur' => 'Urdu'], 'rules' => ['required', 'string', 'max:5']],
                            'date_format' => ['input' => 'select', 'label' => 'Date format', 'options' => $dateOptions, 'rules' => ['required', 'in:' . implode(',', array_keys($dateOptions))], 'help' => 'How dates display across the store & admin.'],
                            'time_format' => ['input' => 'select', 'label' => 'Time format', 'options' => $timeOptions, 'rules' => ['required', 'in:' . implode(',', array_keys($timeOptions))], 'help' => 'How times display.'],
                            'items_per_page' => ['type' => 'int', 'input' => 'number', 'label' => 'Items per page', 'rules' => ['required', 'integer', 'min:1', 'max:100']],
                        ],
                    ],
                ],
            ],

            'store' => [
                'label' => 'Store',
                'icon' => 'storefront',
                'sections' => [
                    [
                        'title' => 'Contact & address',
                        'description' => 'Shown in the storefront footer, invoices and emails.',
                        'fields' => [
                            'address' => ['input' => 'textarea', 'label' => 'Business address', 'rows' => 2, 'max' => 500, 'rules' => ['nullable', 'string', 'max:500']],
                            'phone' => ['input' => 'text', 'label' => 'Phone', 'max' => 30, 'rules' => ['nullable', 'string', 'max:30']],
                            'support_email' => ['input' => 'email', 'label' => 'Support email', 'max' => 255, 'rules' => ['nullable', 'email', 'max:255']],
                            'business_hours' => ['input' => 'text', 'label' => 'Business hours', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'e.g. Mon–Sat, 10am–8pm'],
                        ],
                    ],
                    [
                        'title' => 'Invoice & receipt',
                        'description' => 'How a printed order bill is formatted.',
                        'fields' => [
                            'bill_type' => ['input' => 'select', 'label' => 'Default bill format', 'default' => 'a4', 'rules' => ['required', 'in:a4,thermal'], 'options' => ['a4' => 'A4 — full-page invoice', 'thermal' => 'Thermal — 80mm receipt'], 'help' => 'Default when printing any bill (orders, quotations, purchases). Each print screen still lets you pick A4 or thermal.'],
                            'invoice_footer' => ['input' => 'textarea', 'label' => 'Footer note', 'rows' => 2, 'max' => 500, 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Printed at the bottom of every bill (e.g. return policy or a thank-you).'],
                        ],
                    ],
                ],
            ],

            'payment' => [
                'label' => 'Payment',
                'icon' => 'payments',
                'sections' => [
                    [
                        'title' => 'Payment methods',
                        'fields' => [
                            'cod_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Cash on Delivery', 'help' => 'Let customers pay in cash when the order arrives.'],
                            'qr_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'QR / bank transfer', 'help' => 'Show QR / bank details at checkout.'],
                        ],
                    ],
                    [
                        'title' => 'JazzCash',
                        'fields' => [
                            'jazzcash_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Enable JazzCash'],
                            'jazzcash_merchant_id' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Merchant ID', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                    [
                        'title' => 'Easypaisa',
                        'fields' => [
                            'easypaisa_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Enable Easypaisa'],
                            'easypaisa_store_id' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Store ID', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                ],
            ],

            'shipping' => [
                'label' => 'Shipping',
                'icon' => 'local_shipping',
                'sections' => [
                    [
                        'title' => 'Shipping rates',
                        'description' => 'Amounts are in your store currency.',
                        'fields' => [
                            'flat_rate' => ['type' => 'int', 'input' => 'number', 'label' => 'Flat shipping rate', 'rules' => ['required', 'integer', 'min:0'], 'help' => 'Charged per order.'],
                            'free_over' => ['type' => 'int', 'input' => 'number', 'label' => 'Free shipping over', 'rules' => ['required', 'integer', 'min:0'], 'help' => 'Order subtotal that unlocks free shipping (0 = never).'],
                        ],
                    ],
                ],
            ],

            'tax' => [
                'label' => 'Tax',
                'icon' => 'percent',
                'sections' => [
                    [
                        'title' => 'Tax',
                        'fields' => [
                            'enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Charge tax', 'help' => 'Apply tax to orders at checkout.'],
                            'rate' => ['type' => 'int', 'input' => 'number', 'label' => 'Tax rate (%)', 'rules' => ['required', 'integer', 'min:0', 'max:100']],
                            'inclusive' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Prices include tax', 'help' => 'Product prices already contain the tax amount.'],
                        ],
                    ],
                ],
            ],

            'seo' => [
                'label' => 'SEO',
                'icon' => 'travel_explore',
                'sections' => [
                    [
                        'title' => 'Meta defaults',
                        'fields' => [
                            'title_suffix' => ['input' => 'text', 'label' => 'Title suffix', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Appended to page titles, e.g. “… | Usman Ecommerce”.'],
                            'organization_name' => ['input' => 'text', 'label' => 'Organization name', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255']],
                            'default_meta_description' => ['input' => 'textarea', 'label' => 'Default meta description', 'rows' => 3, 'max' => 500, 'rules' => ['nullable', 'string', 'max:500']],
                            'default_og_image' => ['input' => 'url', 'label' => 'Default social image URL', 'max' => 2048, 'rules' => ['nullable', 'url', 'max:2048']],
                        ],
                    ],
                    [
                        'title' => 'Verification & analytics',
                        'fields' => [
                            'google_analytics_id' => ['input' => 'text', 'label' => 'Google Analytics ID', 'max' => 50, 'rules' => ['nullable', 'string', 'max:50'], 'help' => 'e.g. G-XXXXXXX'],
                            'google_site_verification' => ['input' => 'text', 'label' => 'Google site verification', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                ],
            ],

            'mail' => [
                'label' => 'Mail',
                'icon' => 'mail',
                'sections' => [
                    [
                        'title' => 'Outgoing mail',
                        'description' => 'The “from” identity on transactional emails.',
                        'fields' => [
                            'from_name' => ['input' => 'text', 'label' => 'From name', 'max' => 255, 'rules' => ['required', 'string', 'max:255']],
                            'from_address' => ['input' => 'email', 'label' => 'From address', 'max' => 255, 'rules' => ['required', 'email', 'max:255']],
                        ],
                    ],
                ],
            ],

            'inventory' => [
                'label' => 'Inventory',
                'icon' => 'inventory_2',
                'sections' => [
                    [
                        'title' => 'Stock control',
                        'description' => 'How stock movements behave across purchases, production and sales.',
                        'fields' => [
                            'allow_negative_stock' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Allow negative stock', 'help' => 'Let a sale or consumption push a variant below zero. Off = block when short.', 'default' => false],
                            'costing_method' => ['input' => 'select', 'label' => 'Costing method', 'options' => ['moving_average' => 'Moving average (weighted)', 'fifo' => 'FIFO'], 'rules' => ['required', 'in:moving_average,fifo'], 'default' => 'moving_average', 'help' => 'Moving average is applied to all stock-in today; FIFO is planned.'],
                        ],
                    ],
                ],
            ],

            'pricing' => [
                'label' => 'Pricing',
                'icon' => 'sell',
                'sections' => [
                    [
                        'title' => 'Default markups',
                        'description' => 'Used to suggest selling prices from cost (applied by the pricing helper once enabled).',
                        'fields' => [
                            'default_markup_percent' => ['type' => 'int', 'input' => 'number', 'label' => 'Default markup (%)', 'rules' => ['required', 'integer', 'min:0', 'max:1000'], 'default' => 30, 'help' => 'Retail = cost × (1 + markup%).'],
                            'wholesale_discount_percent' => ['type' => 'int', 'input' => 'number', 'label' => 'Wholesale discount (%)', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 10, 'help' => 'Wholesale price = retail − this %.'],
                        ],
                    ],
                ],
            ],

            'numbering' => [
                'label' => 'Numbering',
                'icon' => 'tag',
                'sections' => [
                    [
                        'title' => 'Document prefixes',
                        'description' => 'Prepended to the running number on each document type.',
                        'fields' => [
                            'order_prefix' => ['input' => 'text', 'label' => 'Order prefix', 'max' => 12, 'rules' => ['required', 'string', 'max:12'], 'default' => 'ORD-'],
                            'quotation_prefix' => ['input' => 'text', 'label' => 'Quotation prefix', 'max' => 12, 'rules' => ['required', 'string', 'max:12'], 'default' => 'QUO-'],
                            'purchase_prefix' => ['input' => 'text', 'label' => 'Purchase prefix', 'max' => 12, 'rules' => ['required', 'string', 'max:12'], 'default' => 'PUR-'],
                            'production_prefix' => ['input' => 'text', 'label' => 'Production prefix', 'max' => 12, 'rules' => ['required', 'string', 'max:12'], 'default' => 'PRD-'],
                        ],
                    ],
                ],
            ],

            'pos' => [
                'label' => 'POS',
                'icon' => 'point_of_sale',
                'sections' => [
                    [
                        'title' => 'Counter & receipt',
                        'fields' => [
                            'receipt_footer' => ['input' => 'textarea', 'label' => 'Receipt footer', 'rows' => 2, 'max' => 500, 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Printed at the bottom of POS receipts.'],
                            'auto_print_receipt' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Show receipt after sale', 'help' => 'Surface the print link immediately after a sale completes.', 'default' => true],
                        ],
                    ],
                ],
            ],

            'quotation' => [
                'label' => 'Quotation',
                'icon' => 'request_quote',
                'sections' => [
                    [
                        'title' => 'Defaults',
                        'description' => 'Pre-filled onto every new quotation.',
                        'fields' => [
                            'default_validity_days' => ['type' => 'int', 'input' => 'number', 'label' => 'Valid for (days)', 'rules' => ['required', 'integer', 'min:1', 'max:365'], 'default' => 14, 'help' => 'Sets the “valid until” date on a new quotation.'],
                            'default_terms' => ['input' => 'textarea', 'label' => 'Default terms / notes', 'rows' => 3, 'max' => 2000, 'rules' => ['nullable', 'string', 'max:2000']],
                        ],
                    ],
                ],
            ],

            'social_login' => [
                'label' => 'Social login',
                'icon' => 'passkey',
                'sections' => [
                    [
                        'title' => 'Google',
                        'description' => 'Register BOTH redirect URIs in Google Cloud Console → Credentials — storefront: ' . route('social.callback', 'google') . ' · admin: ' . route('admin.auth.callback', 'google'),
                        'fields' => [
                            'google_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Enable Google sign-in', 'help' => 'Shows the Google button on both the storefront login/register and the admin login.'],
                            'google_client_id' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Client ID', 'rules' => ['nullable', 'string', 'max:255']],
                            'google_client_secret' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Client secret', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                    [
                        'title' => 'Facebook',
                        'description' => 'Register BOTH OAuth redirect URIs in your Facebook app → Facebook Login settings — storefront: ' . route('social.callback', 'facebook') . ' · admin: ' . route('admin.auth.callback', 'facebook'),
                        'fields' => [
                            'facebook_enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Enable Facebook sign-in', 'help' => 'Shows the Facebook button on both the storefront login/register and the admin login.'],
                            'facebook_app_id' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'App ID', 'rules' => ['nullable', 'string', 'max:255']],
                            'facebook_app_secret' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'App secret', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                ],
            ],

            'maps' => [
                'label' => 'Maps & address',
                'icon' => 'map',
                'sections' => [
                    [
                        'title' => 'Google Maps',
                        'description' => 'Powers address search, "use my location" and the draggable map pin on the customer address form. In Google Cloud Console enable the Maps JavaScript API, Places API and Geocoding API, create an API key, and restrict it to your domain.',
                        'fields' => [
                            'enabled' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Enable maps on address form', 'help' => 'Adds search-as-you-type, current-location and a pin. Requires an API key below.'],
                            'google_maps_key' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Google Maps API key', 'rules' => ['nullable', 'string', 'max:255']],
                        ],
                    ],
                    [
                        'title' => 'Address defaults',
                        'description' => 'Used to simplify the customer address form.',
                        'fields' => [
                            'default_country' => ['input' => 'text', 'label' => 'Default country', 'default' => 'Pakistan', 'max' => 120, 'rules' => ['nullable', 'string', 'max:120'], 'help' => 'Pre-filled and hidden on the address form so customers never type it.'],
                            'country_code' => ['input' => 'text', 'label' => 'Country code (ISO-2)', 'default' => 'PK', 'max' => 2, 'rules' => ['nullable', 'string', 'max:2'], 'help' => 'Biases address search to this country, e.g. PK.'],
                            'map_center' => ['input' => 'text', 'label' => 'Default map center', 'default' => '30.3753,69.3451', 'max' => 60, 'rules' => ['nullable', 'string', 'max:60'], 'help' => 'lat,lng the map opens on before a pin is set, e.g. 30.3753,69.3451.'],
                        ],
                    ],
                ],
            ],

            'seo' => [
                'label' => 'SEO & sharing',
                'icon' => 'travel_explore',
                'sections' => [
                    [
                        'title' => 'Search engines',
                        'description' => 'Defaults for pages that don\'t set their own. Keep descriptions under ~160 characters.',
                        'fields' => [
                            'meta_description' => ['input' => 'textarea', 'label' => 'Default meta description', 'rows' => 2, 'max' => 300, 'rules' => ['nullable', 'string', 'max:300']],
                            'meta_keywords' => ['input' => 'text', 'label' => 'Default keywords', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Comma-separated. Optional, minor effect.'],
                            'indexable' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Allow search engines to index this site', 'default' => true, 'help' => 'Turn OFF on a staging site — every page then sends "noindex".'],
                            'google_site_verification' => ['input' => 'text', 'label' => 'Google verification code', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'The content value from Search Console\'s meta-tag method.'],
                        ],
                    ],
                    [
                        'title' => 'Social sharing',
                        'description' => 'Open Graph / Twitter cards shown when a page link is shared.',
                        'fields' => [
                            'og_image' => ['input' => 'text', 'label' => 'Default share image', 'max' => 500, 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Absolute URL or /storage path. 1200×630 works best.'],
                            'twitter_handle' => ['input' => 'text', 'label' => 'Twitter / X handle', 'max' => 50, 'rules' => ['nullable', 'string', 'max:50'], 'help' => 'e.g. @yourstore'],
                            'facebook_url' => ['input' => 'text', 'label' => 'Facebook page URL', 'max' => 255, 'rules' => ['nullable', 'url', 'max:255']],
                            'instagram_url' => ['input' => 'text', 'label' => 'Instagram URL', 'max' => 255, 'rules' => ['nullable', 'url', 'max:255']],
                        ],
                    ],
                ],
            ],
        ];
    }
}
