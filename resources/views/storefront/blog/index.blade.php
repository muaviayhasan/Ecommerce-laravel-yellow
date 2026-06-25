@extends('layouts.storefront')

@section('title', 'Blog — ' . config('app.name'))
@section('meta_description', 'Insights & trends in consumer electronics, design and technology from ' . config('app.name') . '.')

@section('content')
    <div class="bg-background py-12">
        <div class="app-container">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Posts --}}
                <div class="lg:col-span-8 flex flex-col gap-10">
                    @foreach ($posts as $post)
                        <x-storefront.blog-post-card :post="$post" />
                    @endforeach

                    {{-- Pagination (static placeholder) --}}
                    <div class="flex items-center gap-2 mt-2">
                        <span class="w-10 h-10 flex items-center justify-center rounded bg-primary-container text-on-primary-container font-bold">1</span>
                        <a href="{{ route('blog') }}" class="w-10 h-10 flex items-center justify-center rounded border border-outline-variant hover:bg-surface-container transition-colors">2</a>
                        <a href="{{ route('blog') }}" class="w-10 h-10 flex items-center justify-center rounded border border-outline-variant hover:bg-surface-container transition-colors" aria-label="Next page">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
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
