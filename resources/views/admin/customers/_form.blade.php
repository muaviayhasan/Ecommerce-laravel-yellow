@php
    $sections = [
        [
            'title' => 'Customer details',
            'fields' => [
                'name' => ['input' => 'text', 'label' => 'Name', 'max' => 255],
                'email' => ['input' => 'email', 'label' => 'Email', 'max' => 255],
                'phone' => ['input' => 'text', 'label' => 'Phone', 'max' => 30],
                'type' => ['input' => 'select', 'label' => 'Type', 'options' => ['retail' => 'Retail', 'wholesale' => 'Wholesale']],
                'price_tier' => ['input' => 'select', 'label' => 'Price tier', 'options' => ['retail' => 'Retail', 'wholesale' => 'Wholesale'], 'help' => 'Which price list applies to this customer.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive customers can\'t be selected in new orders.'],
            ],
        ],
        [
            'title' => 'Billing & notes',
            'fields' => [
                'opening_balance' => ['input' => 'number', 'label' => 'Opening balance', 'help' => 'Outstanding receivable for credit/wholesale customers.'],
                'address' => ['input' => 'textarea', 'label' => 'Address', 'rows' => 2, 'max' => 1000],
                'notes' => ['input' => 'textarea', 'label' => 'Internal notes', 'rows' => 3, 'max' => 2000],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <x-settings.section :title="$section['title']">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($section['fields'] as $name => $meta)
                    <div @class(['md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true)])>
                        <x-settings.field group="customer" :name="$name" :meta="$meta" :value="data_get($customer, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
