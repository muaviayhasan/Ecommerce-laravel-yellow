@props(['post'])

@php
    $format = data_get($post, 'format', 'standard');
    $isText = $format === 'text';
    $hasOverlay = in_array($format, ['audio', 'video'], true);
    $badge = in_array($format, ['gallery', 'audio', 'video'], true) ? ucfirst($format) : null;
    $url = data_get($post, 'url', route('blog'));
@endphp

<article class="group rounded-xl overflow-hidden border border-outline-variant transition-shadow {{ $isText ? 'bg-surface-container-low p-8' : 'bg-white hover:shadow-md' }}">
    @unless ($isText)
        <a href="{{ $url }}" class="relative block overflow-hidden {{ $format === 'video' ? 'h-[420px] bg-black' : 'h-72' }}">
            <img src="{{ data_get($post, 'image') }}" alt="{{ data_get($post, 'title') }}" loading="lazy"
                class="w-full h-full object-cover {{ $format === 'video' ? 'opacity-80' : '' }} group-hover:scale-105 transition-transform duration-500">
            @if ($hasOverlay)
                <span class="absolute inset-0 flex items-center justify-center {{ $format === 'audio' ? 'bg-black/30' : '' }}">
                    <span class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg {{ $format === 'video' ? 'bg-primary-container text-on-primary-container' : 'bg-white text-primary' }} group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">{{ $format === 'audio' ? 'play_arrow' : 'play_circle' }}</span>
                    </span>
                </span>
            @endif
            @if ($badge)
                <span class="absolute top-4 left-4 bg-primary-container text-on-primary-container px-3 py-1 text-product-title font-bold rounded">{{ $badge }}</span>
            @endif
        </a>
    @endunless

    <div class="{{ $isText ? '' : 'p-8' }}">
        <div class="flex flex-wrap items-center gap-4 text-on-surface-variant text-label-sm mb-4">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">calendar_today</span> {{ data_get($post, 'date') }}</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">folder</span> {{ data_get($post, 'category') }}</span>
            @if (data_get($post, 'reading_time'))
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> {{ $post['reading_time'] }} min read</span>
            @endif
        </div>
        <h2 class="text-headline-md font-bold mb-4">
            <a href="{{ $url }}" class="hover:text-primary transition-colors">{{ data_get($post, 'title') }}</a>
        </h2>
        <p class="text-on-surface-variant text-body-base mb-6 leading-relaxed line-clamp-3">{{ data_get($post, 'excerpt') }}</p>
        <a href="{{ $url }}" class="inline-block bg-primary-container text-on-primary-container px-6 py-2.5 rounded text-product-title font-bold hover:brightness-95 transition-all">Read More</a>
    </div>
</article>
