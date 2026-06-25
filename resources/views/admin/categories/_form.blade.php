@php
    // Form layout (reuses the schema-driven x-settings.field control).
    $sections = [
        [
            'title' => 'Details',
            'fields' => [
                'name' => ['input' => 'text', 'label' => 'Name', 'max' => 255],
                'slug' => ['input' => 'text', 'label' => 'Slug', 'max' => 255, 'help' => 'Leave blank to auto-generate from the name.'],
                'parent_id' => ['input' => 'select', 'label' => 'Parent category', 'options' => ['' => '— None (top level) —'] + $parentOptions],
                'sort_order' => ['input' => 'number', 'label' => 'Sort order', 'help' => 'Lower numbers appear first.'],
                'markup_percent' => ['input' => 'number', 'label' => 'Default markup (%)', 'help' => 'Optional category-level pricing markup.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive categories are hidden from the storefront.'],
            ],
        ],
        [
            'title' => 'Image & description',
            'fields' => [
                'image_media_id' => ['input' => 'media', 'label' => 'Image', 'media' => $mediaItems, 'placeholder' => 'Choose a category image'],
                'description' => ['input' => 'textarea', 'label' => 'Description', 'rows' => 4, 'max' => 5000],
            ],
        ],
        [
            'title' => 'SEO',
            'fields' => [
                'meta_title' => ['input' => 'text', 'label' => 'Meta title', 'max' => 255],
                'meta_image_media_id' => ['input' => 'media', 'label' => 'Social image', 'media' => $mediaItems, 'placeholder' => 'Choose a social image'],
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
                    <div @class([
                        'md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true),
                    ])>
                        <x-settings.field group="category" :name="$name" :meta="$meta" :value="data_get($category, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
