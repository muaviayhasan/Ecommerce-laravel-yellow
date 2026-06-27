@extends('layouts.storefront')

@section('title', 'Blog — ' . config('app.name'))
@section('meta_description', 'Insights & trends in consumer electronics, design and technology from ' . config('app.name') . '.')

@section('content')
    <div class="bg-background py-12">
        <div class="app-container">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Posts --}}
                <div class="lg:col-span-8 flex flex-col gap-10">
                    @forelse ($posts as $post)
                        <x-storefront.blog-post-card :post="$post" />
                    @empty
                        <div class="bg-white rounded-xl border border-outline-variant p-16 text-center">
                            <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">article</span>
                            <p class="mt-4 text-xl font-light text-on-surface-variant">No blog posts published yet.</p>
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
                    <x-storefront.blog-sidebar :categories="$categories" :recentPosts="$recentPosts" :tags="$tags" />
                </div>
            </div>
        </div>
    </div>

    {{-- Quote --}}
    <section class="bg-inverse-surface py-16 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="material-symbols-outlined text-primary-container text-[48px] mb-6">format_quote</span>
            <blockquote class="text-inverse-on-surface italic text-2xl md:text-3xl font-light leading-relaxed mb-8">
                "Quisque a commodo lectus. Nunc vel dolor sed libero venenatis egestas. Cras non volutpat enim. Cras
                molestie purus id lorem sodales, in facilisis erat tristique."
            </blockquote>
            <cite class="text-primary-container text-headline-md not-italic font-bold">– Steve Kowalsky</cite>
        </div>
    </section>

    {{-- CTA banner --}}
    <section class="app-container my-12">
        <div class="bg-surface-container-high p-8 lg:p-10 rounded-xl flex flex-col md:flex-row items-center justify-between gap-8 text-center md:text-left">
            <div>
                <h3 class="text-headline-lg font-bold mb-2">Looking for the latest tech deals? Explore the store.</h3>
                <a href="{{ route('shop') }}" class="text-primary text-body-base underline">Browse all products</a>
            </div>
            <a href="{{ route('shop') }}" class="bg-primary-container text-on-primary-container px-10 py-4 rounded font-bold hover:scale-105 transition-transform whitespace-nowrap">Shop Now</a>
        </div>
    </section>

    <x-storefront.brand-strip />
@endsection
