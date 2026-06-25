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
                            'date_format' => ['input' => 'text', 'label' => 'Date format', 'max' => 20, 'rules' => ['required', 'string', 'max:20'], 'help' => 'PHP date() tokens, e.g. d M Y'],
                            'time_format' => ['input' => 'text', 'label' => 'Time format', 'max' => 20, 'rules' => ['required', 'string', 'max:20'], 'help' => 'e.g. h:i A'],
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
        ];
    }
}
