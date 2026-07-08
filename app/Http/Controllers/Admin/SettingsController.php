<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Media;
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
            new Middleware('can:settings.edit', only: ['update', 'sendTestMail']),
        ];
    }

    /**
     * Send a one-off test email to verify the configured SMTP settings. Sent
     * synchronously so any transport error surfaces immediately in the flash.
     */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_email' => ['required', 'email', 'max:255']]);

        try {
            \Illuminate\Support\Facades\Mail::to($data['test_email'])->send(new \App\Mail\TestMail);

            return back()->with('settings_status', 'Test email sent to ' . $data['test_email'] . '. Check the inbox (and spam).');
        } catch (\Throwable $e) {
            return back()->with('settings_error', 'Could not send: ' . $e->getMessage());
        }
    }

    public function show(string $group = 'general'): View
    {
        $schema = $this->schema();
        abort_unless(isset($schema[$group]), 404);

        $values = Setting::groupWithDefaults($group, $this->defaults($schema[$group]));

        return view('admin.settings.show', [
            'tabs' => collect($schema)->map(fn ($g, $key) => [
                'key' => $key,
                'label' => $g['label'],
                'icon' => $g['icon'],
            ])->values()->all(),
            'group' => $group,
            'config' => $this->hydrateMedia($schema[$group], $values),
            'values' => $values,
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
     * Inject the currently-selected Media into any 'media' field so its picker can
     * preview the existing image (the browse grid itself lazy-loads from the gallery).
     */
    private function hydrateMedia(array $config, array $values): array
    {
        foreach ($config['sections'] as $si => $section) {
            foreach ($section['fields'] as $key => $meta) {
                if (($meta['input'] ?? '') !== 'media' || empty($values[$key])) {
                    continue;
                }
                if ($m = Media::find((int) $values[$key])) {
                    $config['sections'][$si]['fields'][$key]['media'] = [
                        ['id' => $m->id, 'url' => $m->url, 'title' => $m->title ?: basename($m->path)],
                    ];
                }
            }
        }

        return $config;
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

        // Active coupons offered as an optional incentive in abandoned-cart reminders.
        $couponOptions = ['0' => '— No coupon —'] + Coupon::where('is_active', true)
            ->orderBy('code')->pluck('code', 'id')->all();

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
                            'favicon' => ['input' => 'media', 'label' => 'Favicon', 'rules' => ['nullable', 'integer', 'exists:media,id'], 'help' => 'The small icon shown in the browser tab, for the storefront and admin. A square image (PNG, 256×256 or larger) works best — upload it in the Gallery, then pick it here. Leave empty for the default icon.'],
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
                            'whatsapp' => ['input' => 'text', 'label' => 'WhatsApp number', 'max' => 30, 'rules' => ['nullable', 'string', 'max:30'], 'help' => 'Full international format, e.g. 923001234567. Powers the “Chat on WhatsApp” button in the support widget. Leave blank to hide it.'],
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

            'mail' => [
                'label' => 'Mail',
                'icon' => 'mail',
                'sections' => [
                    [
                        'title' => 'Outgoing mail',
                        'description' => 'The “from” identity shown on every email the store sends.',
                        'fields' => [
                            'from_name' => ['input' => 'text', 'label' => 'From name', 'max' => 255, 'rules' => ['required', 'string', 'max:255'], 'default' => (string) config('mail.from.name')],
                            'from_address' => ['input' => 'email', 'label' => 'From address', 'max' => 255, 'rules' => ['required', 'email', 'max:255'], 'default' => (string) config('mail.from.address')],
                        ],
                    ],
                    [
                        'title' => 'SMTP server',
                        'description' => 'Credentials from your email provider (e.g. Gmail, Mailgun, SES, Zoho). Leave the host blank to fall back to the server’s .env configuration. Use “Send test email” below to verify.',
                        'fields' => [
                            'host' => ['input' => 'text', 'label' => 'SMTP host', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'placeholder' => 'smtp.mailgun.org', 'help' => 'Your provider’s outgoing mail server.'],
                            'port' => ['type' => 'int', 'input' => 'number', 'label' => 'Port', 'rules' => ['nullable', 'integer', 'min:1', 'max:65535'], 'placeholder' => '587', 'help' => '587 for TLS, 465 for SSL.'],
                            'encryption' => ['input' => 'select', 'label' => 'Encryption', 'options' => ['tls' => 'TLS (recommended)', 'ssl' => 'SSL', 'none' => 'None'], 'default' => 'tls', 'rules' => ['nullable', 'in:tls,ssl,none']],
                            'username' => ['input' => 'text', 'label' => 'Username', 'max' => 255, 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Usually your full email address.'],
                            'password' => ['type' => 'encrypted', 'input' => 'secret', 'label' => 'Password', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Stored encrypted. Leave blank to keep the current password.'],
                        ],
                    ],
                ],
            ],

            'emails' => [
                'label' => 'Email Notifications',
                'icon' => 'mark_email_read',
                'sections' => [
                    [
                        'title' => 'Customer account',
                        'description' => 'Emails sent to customers around their account. Switch any off to stop sending it.',
                        'fields' => [
                            'registration_welcome' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Welcome email on registration', 'default' => true, 'help' => 'A greeting when a new account is created.'],
                            'email_verification' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Verify email address', 'default' => true, 'help' => 'Sends a confirmation link to verify the address. Turning this off means addresses are never verified.'],
                            'password_reset' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Password reset link', 'default' => true, 'help' => 'Required for “forgot password” to work. Off = customers cannot reset their password by email.'],
                            'password_changed' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Password changed confirmation', 'default' => true, 'help' => 'A security notice after the password changes.'],
                        ],
                    ],
                    [
                        'title' => 'Orders',
                        'description' => 'Order lifecycle emails to the customer who placed the order.',
                        'fields' => [
                            'order_confirmation' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Order confirmation', 'default' => true, 'help' => 'Sent right after an order is placed, with the full summary.'],
                            'order_status_update' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Order status updates', 'default' => true, 'help' => 'Sent when the order moves to processing, shipped, delivered, etc.'],
                        ],
                    ],
                    [
                        'title' => 'Cart recovery',
                        'description' => 'Automatically remind shoppers who reach checkout but don’t complete their order. Reminders only go to people who gave an email and haven’t unsubscribed.',
                        'fields' => [
                            'abandoned_cart' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Send abandoned-cart reminders', 'default' => false, 'help' => 'Master switch. Off = no carts are stored and no reminders are sent.'],
                            'abandoned_cart_first_delay_hours' => ['type' => 'int', 'input' => 'number', 'label' => 'First reminder after (hours)', 'rules' => ['nullable', 'integer', 'min:0', 'max:168'], 'default' => 1, 'help' => 'How long an unfinished cart sits idle before the first reminder.'],
                            'abandoned_cart_followup_delay_hours' => ['type' => 'int', 'input' => 'number', 'label' => 'Space follow-ups by (hours)', 'rules' => ['nullable', 'integer', 'min:1', 'max:336'], 'default' => 20, 'help' => 'Gap between each reminder after the first.'],
                            'abandoned_cart_max_reminders' => ['type' => 'int', 'input' => 'number', 'label' => 'Maximum reminders per cart', 'rules' => ['nullable', 'integer', 'min:1', 'max:5'], 'default' => 2, 'help' => 'Total reminders before we stop nudging a cart.'],
                            'abandoned_cart_coupon_id' => ['type' => 'int', 'input' => 'select', 'label' => 'Incentive coupon (optional)', 'options' => $couponOptions, 'rules' => ['nullable', 'integer'], 'default' => 0, 'help' => 'Included in the reminder as a gentle push to complete the order.'],
                        ],
                    ],
                    [
                        'title' => 'Quotations',
                        'fields' => [
                            'quotation_sent' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Quotation sent to customer', 'default' => true, 'help' => 'Emailed when a quotation is marked as “sent”.'],
                        ],
                    ],
                    [
                        'title' => 'Admin alerts',
                        'description' => 'Internal notifications sent to your support email (Settings → General).',
                        'fields' => [
                            'admin_new_order' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'New order received', 'default' => true, 'help' => 'Notify staff whenever a customer places an order.'],
                            'admin_new_subscriber' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'New newsletter signup', 'default' => true, 'help' => 'Notify staff when someone subscribes to the newsletter.'],
                            'admin_new_quote_request' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'New quote request', 'default' => true, 'help' => 'Notify staff when a customer submits the “Request a quote” form.'],
                            'admin_new_contact' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'New contact message', 'default' => true, 'help' => 'Notify staff when someone submits the “Contact us” form.'],
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

            'system' => [
                'label' => 'System',
                'icon' => 'monitoring',
                'sections' => [
                    [
                        'title' => 'Error logging',
                        'description' => 'Capture unhandled exceptions to the database so you can review and fix them in Admin → Error Logs.',
                        'fields' => [
                            'log_errors' => ['type' => 'bool', 'input' => 'toggle', 'label' => 'Store errors in the database', 'default' => true, 'help' => 'Off = exceptions are only written to the normal log files.'],
                            'error_log_retention_days' => ['type' => 'int', 'input' => 'number', 'label' => 'Auto-delete resolved after (days)', 'rules' => ['nullable', 'integer', 'min:0', 'max:3650'], 'default' => 30, 'help' => 'Resolved errors older than this are pruned automatically. 0 = keep forever.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
