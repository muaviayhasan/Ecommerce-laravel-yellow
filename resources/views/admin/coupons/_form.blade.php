@php
    $values = [
        'code' => $coupon->code,
        'description' => $coupon->description,
        'type' => $coupon->type ?? 'percent',
        'value' => $coupon->value,
        'min_subtotal' => $coupon->min_subtotal,
        'max_uses' => $coupon->max_uses,
        'usage_limit_per_customer' => $coupon->usage_limit_per_customer,
        'starts_at' => $coupon->starts_at?->format('Y-m-d\TH:i'),
        'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
        'is_active' => $coupon->is_active ?? true,
    ];

    $sections = [
        [
            'title' => 'Coupon',
            'fields' => [
                'code' => ['input' => 'text', 'label' => 'Code', 'max' => 50, 'help' => 'What customers type at checkout (auto-uppercased). e.g. WELCOME10', 'placeholder' => 'WELCOME10'],
                'description' => ['input' => 'text', 'label' => 'Description', 'max' => 255, 'help' => 'Internal note (optional).'],
                'type' => ['input' => 'select', 'label' => 'Discount type', 'options' => ['percent' => 'Percentage (%)', 'fixed' => 'Fixed amount']],
                'value' => ['input' => 'number', 'label' => 'Value', 'help' => '10 = 10% for a percentage coupon, or a fixed money amount.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive coupons are rejected at checkout.'],
            ],
        ],
        [
            'title' => 'Limits',
            'fields' => [
                'min_subtotal' => ['input' => 'number', 'label' => 'Minimum subtotal', 'help' => 'Cart subtotal required to use it (blank = none).'],
                'max_uses' => ['input' => 'number', 'label' => 'Total redemptions', 'help' => 'Overall cap across all customers (blank = unlimited).'],
                'usage_limit_per_customer' => ['input' => 'number', 'label' => 'Per-customer limit', 'help' => 'Times one customer may use it (blank = unlimited).'],
            ],
        ],
        [
            'title' => 'Validity window',
            'fields' => [
                'starts_at' => ['input' => 'datetime-local', 'label' => 'Starts at', 'help' => 'Blank = active immediately.'],
                'expires_at' => ['input' => 'datetime-local', 'label' => 'Expires at', 'help' => 'Blank = never expires.'],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <x-settings.section :title="$section['title']">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($section['fields'] as $name => $meta)
                    <div @class(['md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle'], true)])>
                        <x-settings.field group="coupon" :name="$name" :meta="$meta" :value="$values[$name]" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
