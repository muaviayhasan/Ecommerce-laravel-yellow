@extends('layouts.storefront')

@section('title', 'Blog — ' . config('app.name'))
@section('meta_description', 'Insights & trends in consumer electronics, design and technology from ' . config('app.name') . '.')

@section('content')
    {{-- Page header — dark band with layered brand glows, a faint appliance
         motif and category quick-filters so readers can jump straight in. --}}
    <div class="relative overflow-hidden bg-inverse-surface text-inverse-on-surface">
        {{-- Decorative yellow glows --}}
        <div class="pointer-events-none absolute -top-28 -right-20 w-96 h-96 rounded-full bg-primary-container/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-36 -left-24 w-96 h-96 rounded-full bg-primary-container/10 blur-3xl" aria-hidden="true"></div>

        {{-- Faint appliance icon strip along the bottom --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-3 hidden lg:flex items-end justify-between px-16 opacity-[0.07]" aria-hidden="true">
            @foreach (['ac_unit', 'mode_fan', 'local_laundry_service', 'water_heater', 'solar_power', 'kitchen', 'microwave', 'blender'] as $heroIcon)
                <span class="material-symbols-outlined" style="font-size:44px;">{{ $heroIcon }}</span>
            @endforeach
        </div>

        <div class="app-container relative py-12 md:py-16 text-center">
            <p class="inline-flex items-center gap-2 border border-primary-container/50 text-primary-container rounded-full px-4 py-1.5 font-bold uppercase tracking-widest text-label-sm mb-4">
                <span class="material-symbols-outlined text-[16px]">auto_stories</span>
                {{ config('app.name') }} Insights
            </p>
            <h1 class="text-3xl md:text-headline-lg font-bold">The Appliance Blog<span class="text-primary-container">.</span></h1>
            <p class="text-inverse-on-surface/80 mt-3 max-w-2xl mx-auto">Buying guides, honest reviews and practical maintenance tips for coolers, geysers, fans, washing machines and solar.</p>

            {{-- Category quick filters --}}
            @if (! empty($categories))
                <nav class="mt-8 flex flex-wrap justify-center gap-2.5" aria-label="Blog categories">
                    <a href="{{ route('blog') }}"
                        @class([
                            'px-4 py-2 rounded-full text-label-sm font-semibold border transition-colors',
                            'bg-primary-container text-on-primary-container border-primary-container' => $activeFilter === null,
                            'border-inverse-on-surface/20 text-inverse-on-surface/85 hover:border-primary-container hover:text-primary-container' => $activeFilter !== null,
                        ])>All posts</a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('blog', ['category' => $cat['slug']]) }}"
                            @class([
                                'px-4 py-2 rounded-full text-label-sm font-semibold border transition-colors',
                                'bg-primary-container text-on-primary-container border-primary-container' => $activeCategory === $cat['slug'],
                                'border-inverse-on-surface/20 text-inverse-on-surface/85 hover:border-primary-container hover:text-primary-container' => $activeCategory !== $cat['slug'],
                            ])>{{ $cat['name'] }} <span class="opacity-70">({{ $cat['count'] }})</span></a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>

    <div class="bg-background py-12">
        <div class="app-container">
            {{-- Active filter bar --}}
            @if ($activeFilter)
                <div class="mb-8 flex flex-wrap items-center gap-3 bg-white border border-outline-variant rounded-xl px-5 py-3.5">
                    <span class="text-on-surface-variant text-body-base">
                        @switch($activeFilter['type'])
                            @case('category') Showing posts in @break
                            @case('tag') Posts tagged @break
                            @default Search results for @break
                        @endswitch
                        <span class="font-bold text-on-surface">{{ $activeFilter['label'] }}</span>
                        <span class="text-on-surface-variant">· {{ $posts->total() }} {{ Str::plural('post', $posts->total()) }}</span>
                    </span>
                    <a href="{{ route('blog') }}" class="ml-auto inline-flex items-center gap-1 text-label-sm font-semibold text-primary hover:underline">
                        <span class="material-symbols-outlined text-[16px]">close</span> Clear filter
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Posts --}}
                <div class="lg:col-span-8 flex flex-col gap-10">
                    @forelse ($posts as $post)
                        <x-storefront.blog-post-card :post="$post" />
                    @empty
                        <div class="bg-white rounded-xl border border-outline-variant p-16 text-center">
                            <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">{{ $activeFilter ? 'search_off' : 'article' }}</span>
                            @if ($activeFilter)
                                <p class="mt-4 text-xl font-light text-on-surface-variant">No posts found for “{{ $activeFilter['label'] }}”.</p>
                                <a href="{{ route('blog') }}" class="inline-block mt-4 text-primary font-semibold hover:underline">← Back to all posts</a>
                            @else
                                <p class="mt-4 text-xl font-light text-on-surface-variant">No blog posts published yet.</p>
                            @endif
                        </div>
                    @endforelse

                    {{-- Pagination --}}
                    @if ($posts->hasPages())
                        <div class="flex items-center gap-2 mt-2">
                            @if (! $posts->onFirstPage())
                                <a href="{{ $posts->previousPageUrl() }}" aria-label="Previous page" class="w-10 h-10 flex items-center justify-center rounded border border-outline-variant hover:bg-surface-container transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                                </a>
                            @endif
                            @foreach ($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(), $posts->currentPage() + 2)) as $page => $url)
                                @if ($page == $posts->currentPage())
                                    <span class="w-10 h-10 flex items-center justify-center rounded bg-primary-container text-on-primary-container font-bold">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded border border-outline-variant hover:bg-surface-container transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if ($posts->hasMorePages())
                                <a href="{{ $posts->nextPageUrl() }}" aria-label="Next page" class="w-10 h-10 flex items-center justify-center rounded border border-outline-variant hover:bg-surface-container transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-4">
                    <x-storefront.blog-sidebar :categories="$categories" :recentPosts="$recentPosts" :tags="$tags"
                        :activeCategory="$activeCategory" :activeTag="$activeTag" />
                </div>
            </div>
        </div>
    </div>

    {{-- CTA banner --}}
    <section class="app-container my-12">
        <div class="bg-surface-container-high p-8 lg:p-10 rounded-xl flex flex-col md:flex-row items-center justify-between gap-8 text-center md:text-left">
            <div>
                <h3 class="text-headline-lg font-bold mb-2">Ready to upgrade your home appliances?</h3>
                <a href="{{ route('shop') }}" class="text-primary text-body-base underline">Browse the full range in our store</a>
            </div>
            <a href="{{ route('shop') }}" class="bg-primary-container text-on-primary-container px-10 py-4 rounded font-bold hover:scale-105 transition-transform whitespace-nowrap">Shop Now</a>
        </div>
    </section>

    <x-storefront.brand-strip />
@endsection
