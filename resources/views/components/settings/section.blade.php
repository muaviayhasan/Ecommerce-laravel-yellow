@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest dark:bg-surface-container p-6 rounded-xl border border-outline-variant']) }}>
    @if ($title)
        <div class="mb-5">
            <h3 class="text-lg font-bold text-on-surface">{{ $title }}</h3>
            @if ($description)
                <p class="text-sm text-on-surface-variant mt-0.5">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
