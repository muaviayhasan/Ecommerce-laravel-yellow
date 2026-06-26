@php
    $sections = [
        [
            'title' => 'Supplier details',
            'fields' => [
                'name' => ['input' => 'text', 'label' => 'Name', 'max' => 255],
                'company' => ['input' => 'text', 'label' => 'Company', 'max' => 255],
                'phone' => ['input' => 'text', 'label' => 'Phone', 'max' => 30],
                'email' => ['input' => 'email', 'label' => 'Email', 'max' => 255],
                'tax_number' => ['input' => 'text', 'label' => 'Tax / NTN number', 'max' => 100],
                'payment_terms' => ['input' => 'text', 'label' => 'Payment terms', 'max' => 255, 'help' => 'e.g. Net 30, COD.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive suppliers are hidden from new purchases.'],
            ],
        ],
        [
            'title' => 'Balance & notes',
            'fields' => [
                'opening_balance' => ['input' => 'number', 'label' => 'Opening balance', 'help' => 'Amount already owed to this supplier (payable).'],
                'address' => ['input' => 'textarea', 'label' => 'Address', 'rows' => 2, 'max' => 1000],
                'notes' => ['input' => 'textarea', 'label' => 'Notes', 'rows' => 3, 'max' => 2000],
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
                        <x-settings.field group="supplier" :name="$name" :meta="$meta" :value="data_get($supplier, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
