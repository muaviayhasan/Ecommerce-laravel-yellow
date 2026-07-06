@php
    // Form layout (reuses the schema-driven x-settings.field control).
    $sections = [
        [
            'title' => 'Text',
            'fields' => [
                'kicker' => ['input' => 'text', 'label' => 'Kicker', 'max' => 255, 'help' => 'Small uppercase label above the title (e.g. “Catch the hottest”).'],
                'title' => ['input' => 'text', 'label' => 'Title', 'max' => 255, 'help' => 'Required. Main heading (e.g. “Deals”).'],
                'subtitle' => ['input' => 'text', 'label' => 'Subtitle', 'max' => 255, 'help' => 'Optional line under the title (e.g. “In Cameras”).'],
            ],
        ],
        [
            'title' => 'Call to action',
            'description' => 'Choose how the bottom row displays. “Shop link” shows a “Shop now” label; “Price” shows currency + amount + cents; “Percentage” shows amount + %.',
            'fields' => [
                'display_type' => ['input' => 'select', 'label' => 'Display style', 'select2' => false, 'options' => [
                    'shop' => 'Shop link (“Shop now”)',
                    'price' => 'Price (e.g. From $749.99)',
                    'percent' => 'Percentage (e.g. Up to 70%)',
                ]],
                'prefix' => ['input' => 'text', 'label' => 'Prefix', 'max' => 255, 'help' => 'Used by Price/Percentage (e.g. “From”, “Up to”).'],
                'currency' => ['input' => 'text', 'label' => 'Currency symbol', 'max' => 8, 'placeholder' => '$', 'help' => 'Price only.'],
                'amount' => ['input' => 'text', 'label' => 'Amount', 'max' => 32, 'help' => 'Price/Percentage number (e.g. “749” or “70”).'],
                'cents' => ['input' => 'text', 'label' => 'Cents', 'max' => 8, 'help' => 'Price only — the small superscript (e.g. “99”).'],
                'url' => ['input' => 'text', 'label' => 'Link', 'max' => 2048, 'placeholder' => '/shop', 'help' => 'Where the card links to. Leave blank for the Shop page.'],
            ],
        ],
        [
            'title' => 'Image',
            'fields' => [
                'image_media_id' => ['input' => 'media', 'label' => 'Image', 'media' => $mediaItems, 'placeholder' => 'Choose a promo image'],
                'image_alt' => ['input' => 'text', 'label' => 'Image alt text', 'max' => 255, 'help' => 'Describes the image for screen readers.'],
            ],
        ],
        [
            'title' => 'Display',
            'fields' => [
                'sort_order' => ['input' => 'number', 'label' => 'Sort order', 'help' => 'Lower numbers appear first.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive cards are hidden from the storefront.'],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <x-settings.section :title="$section['title']" :description="$section['description'] ?? null">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($section['fields'] as $name => $meta)
                    <div @class([
                        'md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true),
                    ])>
                        <x-settings.field group="promo_card" :name="$name" :meta="$meta" :value="data_get($card, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
