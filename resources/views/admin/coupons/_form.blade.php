@php
    $values = [
        'code' => $coupon->code,
        'description' => $coupon->description,
        'type' => $coupon->type ?? 'percent',
        'value' => $coupon->value,
        'min_subtotal' => $coupon->min_subtotal,
        'max_uses' => $coupon->max_uses,
        'usage_limit_per_customer' => $coupon->usage_limit_per_customer,
        'first_order_only' => $coupon->first_order_only ?? false,
        'starts_at' => $coupon->starts_at?->format('Y-m-d\TH:i'),
        'expires_at' => $coupon->expires_at?->format('Y-m-d\TH:i'),
        'is_active' => $coupon->is_active ?? true,
    ];

    // Customers this coupon is already restricted to (edit screen); empty on create.
    $selectedCustomerIds = ($coupon->exists ? $coupon->customers->pluck('id')->all() : []);

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
                'usage_limit_per_customer' => ['input' => 'number', 'label' => 'Per-customer limit', 'help' => 'How many times each customer may use it. Set to 1 for once-per-customer. Blank = unlimited.'],
                'first_order_only' => ['input' => 'toggle', 'label' => 'First order only', 'help' => 'Only customers with no previous orders (a new-customer coupon) — separate from the per-customer limit above.'],
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

    <x-settings.section title="Who can use it">
        <label for="customer_ids" class="block text-sm font-medium text-on-surface-variant mb-1.5">Restrict to specific customers</label>
        <select id="customer_ids" name="customer_ids[]" multiple data-placeholder="Leave empty for a public coupon"
            class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
            @foreach ($customers as $c)
                <option value="{{ $c->id }}" @selected(in_array($c->id, old('customer_ids', $selectedCustomerIds)))>{{ $c->name }} — {{ $c->email }}</option>
            @endforeach
        </select>
        <p class="text-xs text-outline mt-1.5">Leave empty for a <span class="font-medium">public</span> coupon (anyone can use it). Selected customers are matched by the email they check out with.</p>
        @error('customer_ids')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
        @error('customer_ids.*')<p class="text-xs text-error mt-1">One of the selected customers is invalid.</p>@enderror
    </x-settings.section>
</div>
