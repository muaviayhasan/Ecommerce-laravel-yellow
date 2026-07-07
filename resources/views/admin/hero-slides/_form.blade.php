@php
    // Form layout (reuses the schema-driven x-settings.field control).
    $sections = [
        [
            'title' => 'Headline',
            'description' => 'The hero renders these on separate lines: line 2 is bold, and the tail + highlight share the last line with the highlight coloured.',
            'fields' => [
                'kicker' => ['input' => 'text', 'label' => 'Kicker', 'max' => 255, 'help' => 'Small uppercase label above the heading (e.g. “Power meets portability”).'],
                'line1' => ['input' => 'text', 'label' => 'Heading line 1', 'max' => 255, 'help' => 'Required. First line of the heading.'],
                'line2' => ['input' => 'text', 'label' => 'Heading line 2 (bold)', 'max' => 255],
                'tail' => ['input' => 'text', 'label' => 'Lead-in text', 'max' => 255, 'help' => 'Text before the highlight (e.g. “SAVE UP TO”).'],
                'highlight' => ['input' => 'text', 'label' => 'Highlight', 'max' => 255, 'help' => 'Coloured emphasis (e.g. “30% OFF” or “Rs 4,999”).'],
            ],
        ],
        [
            'title' => 'Button',
            'fields' => [
                'cta_label' => ['input' => 'text', 'label' => 'Button label', 'max' => 255, 'help' => 'Leave blank to hide the button.'],
                'cta_url' => ['input' => 'text', 'label' => 'Button link', 'max' => 2048, 'placeholder' => '/shop', 'help' => 'Absolute or relative URL. Leave blank to link to the Shop page.'],
            ],
        ],
        [
            'title' => 'Image',
            'fields' => [
                'image_media_id' => ['input' => 'media', 'label' => 'Image', 'media' => $mediaItems, 'placeholder' => 'Choose a slide image'],
                'image_alt' => ['input' => 'text', 'label' => 'Image alt text', 'max' => 255, 'help' => 'Describes the image for screen readers.'],
            ],
        ],
        [
            'title' => 'Display',
            'fields' => [
                'sort_order' => ['input' => 'number', 'label' => 'Sort order', 'help' => 'Lower numbers appear first.'],
                'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive slides are hidden from the storefront.'],
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
                        <x-settings.field group="hero_slide" :name="$name" :meta="$meta" :value="data_get($slide, $name)" />
                    </div>
                @endforeach
            </div>
        </x-settings.section>
    @endforeach
</div>
