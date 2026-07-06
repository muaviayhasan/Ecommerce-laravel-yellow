@php
    // Form layout (reuses the schema-driven x-settings.field control).
    $fields = [
        'icon' => ['input' => 'text', 'label' => 'Icon', 'max' => 255, 'placeholder' => 'local_shipping', 'help' => 'A Material Symbols icon name. Browse names at fonts.google.com/icons (e.g. local_shipping, thumb_up, cached, account_balance_wallet, sell).'],
        'title' => ['input' => 'text', 'label' => 'Title', 'max' => 255, 'help' => 'Required. Bold line (e.g. “Free Delivery”).'],
        'subtitle' => ['input' => 'text', 'label' => 'Subtitle', 'max' => 255, 'help' => 'Smaller line under the title (e.g. “from Rs 5,000”).'],
        'sort_order' => ['input' => 'number', 'label' => 'Sort order', 'help' => 'Lower numbers appear first.'],
        'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Inactive items are hidden from the storefront.'],
    ];
@endphp

{{-- One Alpine scope wraps both the preview and the fields; an input listener
     (delegated by field id) keeps the preview in sync as the admin types. --}}
<div class="space-y-6"
    x-data="{
        icon: @js(old('icon', data_get($item, 'icon') ?? 'local_shipping')),
        title: @js(old('title', data_get($item, 'title') ?? '')),
        subtitle: @js(old('subtitle', data_get($item, 'subtitle') ?? '')),
    }"
    @input="
        if ($event.target.id === 'info_bar_item_icon') icon = $event.target.value;
        if ($event.target.id === 'info_bar_item_title') title = $event.target.value;
        if ($event.target.id === 'info_bar_item_subtitle') subtitle = $event.target.value;
    ">

    {{-- Live preview --}}
    <x-settings.section title="Preview">
        <div class="flex items-center gap-4 px-6 py-4 rounded-lg border border-outline-variant bg-surface-container-low w-fit">
            <span class="material-symbols-outlined text-primary-container text-4xl" x-text="icon || 'help'"></span>
            <div>
                <p class="text-body-base font-bold" x-text="title || 'Title'"></p>
                <p class="text-label-sm text-on-surface-variant" x-text="subtitle || 'Subtitle'"></p>
            </div>
        </div>
        <p class="text-xs text-outline mt-2">Updates as you type below.</p>
    </x-settings.section>

    <x-settings.section title="Item">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            @foreach ($fields as $name => $meta)
                <div @class([
                    'md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true),
                ])>
                    <x-settings.field group="info_bar_item" :name="$name" :meta="$meta" :value="data_get($item, $name)" />
                </div>
            @endforeach
        </div>
    </x-settings.section>
</div>
