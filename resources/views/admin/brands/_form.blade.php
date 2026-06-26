@php
    $sections = [
        [
            'title' => 'Details',
            'fields' => [
                'name' => ['input' => 'text', 'label' => 'Name', 'max' => 255],
                'slug' => ['input' => 'text', 'label' => 'Slug', 'max' => 255, 'help' => 'Leave blank to auto-generate from the name.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive brands are hidden from the storefront.'],
            ],
        ],
        [
            'title' => 'Logo & description',
            'fields' => [
                'logo_media_id' => ['input' => 'media', 'label' => 'Logo', 'media' => $mediaItems, 'placeholder' => 'Choose a brand logo'],
                'description' => ['input' => 'textarea', 'label' => 'Description', 'rows' => 4, 'max' => 5000],
            ],
        ],
        [
            'title' => 'SEO',
            'fields' => [
                'meta_title' => ['input' => 'text', 'label' => 'Meta title', 'max' => 255],
                'meta_description' => ['input' => 'textarea', 'label' => 'Meta description', 'rows' => 3, 'max' => 255],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <x-settings.section :title="$section['title']">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                @foreach ($section['fields'] as $name => $meta)
                    <div @class(['md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea', 'media'], true)])>
                        <x-settings.field group="brand" :name="$name" :meta="$meta" :value="data_get($brand, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
