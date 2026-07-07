@extends('layouts.storefront')

@section('title', 'Blog — ' . config('app.name'))
@section('meta_description', 'Insights & trends in consumer electronics, design and technology from ' . config('app.name') . '.')

@section('content')
    {{-- Page header --}}
    <div class="bg-inverse-surface text-inverse-on-surface py-14">
        <div class="app-container text-center">
            <p class="text-primary-container font-bold uppercase tracking-widest text-label-sm mb-2">{{ config('app.name') }} Insights</p>
            <h1 class="text-3xl md:text-headline-lg font-bold">The Appliance Blog</h1>
            <p class="text-inverse-on-surface/80 mt-3 max-w-2xl mx-auto">Buying guides, honest reviews and practical maintenance tips for coolers, geysers, fans, washing machines and solar.</p>
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
