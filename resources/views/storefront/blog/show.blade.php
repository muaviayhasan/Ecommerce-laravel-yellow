@extends('layouts.storefront')

@section('title', $post['title'] . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post['excerpt'] ?? $post['title']), 155))

@section('content')
    {{-- Breadcrumbs --}}
    <div class="bg-surface-container-low py-4">
        <div class="app-container">
            <nav class="flex flex-wrap items-center gap-2 text-label-sm text-on-surface-variant" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('blog') }}" class="hover:text-primary transition-colors">Blog</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-medium line-clamp-1">{{ $post['title'] }}</span>
            </nav>
        </div>
    </div>

    <div class="bg-background py-12">
        <div class="app-container">
            <div class="flex flex-col lg:flex-row gap-12">
                {{-- Article --}}
                <article class="flex-1 min-w-0">
                    <div class="rounded-lg overflow-hidden mb-8 border border-outline-variant">
                        <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="w-full aspect-[16/9] object-cover">
                    </div>

                    <h1 class="text-headline-lg font-bold mb-4">{{ $post['title'] }}</h1>
                    <div class="flex flex-wrap items-center gap-4 text-label-sm text-on-surface-variant mb-8">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">folder</span> {{ $post['category'] }}</span>
                        <span class="text-outline-variant">|</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_today</span> {{ $post['date'] }}</span>
                        <span class="text-outline-variant">|</span>
                        <a href="#comments" class="flex items-center gap-1 hover:text-primary transition-colors"><span class="material-symbols-outlined text-[16px]">chat_bubble</span> Leave a comment</a>
                    </div>

                    <div class="text-body-base text-on-surface-variant space-y-6 leading-relaxed">
                        @foreach ($post['body'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>

                    {{-- Author --}}
                    <div class="mt-12 p-6 lg:p-8 bg-surface-container border border-outline-variant rounded-lg flex flex-col sm:flex-row gap-6 items-start">
                        <img src="https://picsum.photos/seed/usman-blog-author/200/200" alt="{{ $post['author'] ?? 'Author' }}" class="w-24 h-24 rounded-lg object-cover shrink-0">
                        <div>
                            <h3 class="text-headline-md font-medium mb-2 capitalize">{{ $post['author'] ?? 'admin' }}</h3>
                            <p class="text-body-base text-on-surface-variant leading-relaxed">
                                The lead editor and technology enthusiast at {{ config('app.name') }} — passionate about
                                the frontiers of consumer electronics, space exploration and sustainable hardware design.
                            </p>
                        </div>
                    </div>

                    {{-- Prev / next --}}
                    <div class="mt-12 py-8 border-y border-outline-variant flex justify-between gap-4">
                        @if ($prev)
                            <a href="{{ $prev['url'] }}" class="flex flex-col gap-1 group text-left max-w-[45%]">
                                <span class="text-label-sm text-outline uppercase tracking-wider flex items-center gap-1 group-hover:text-primary transition-colors"><span class="material-symbols-outlined text-[14px]">arrow_back</span> Previous</span>
                                <span class="text-product-title text-on-surface group-hover:text-primary transition-colors line-clamp-1">{{ $prev['title'] }}</span>
                            </a>
                        @else
                            <span></span>
                        @endif
                        @if ($next)
                            <a href="{{ $next['url'] }}" class="flex flex-col gap-1 group text-right max-w-[45%] ml-auto">
                                <span class="text-label-sm text-outline uppercase tracking-wider flex items-center justify-end gap-1 group-hover:text-primary transition-colors">Next <span class="material-symbols-outlined text-[14px]">arrow_forward</span></span>
                                <span class="text-product-title text-on-surface group-hover:text-primary transition-colors line-clamp-1">{{ $next['title'] }}</span>
                            </a>
                        @endif
                    </div>

                    {{-- Comments --}}
                    <div class="mt-16" id="comments">
                        <h2 class="text-headline-md font-bold mb-4">Leave a Reply</h2>
                        <p class="text-body-base text-on-surface-variant mb-6">Your email address will not be published. Required fields are marked *</p>
                        <form class="space-y-6" onsubmit="return false">
                            <div>
                                <label for="comment" class="block text-label-sm font-bold mb-2">Comment *</label>
                                <textarea id="comment" rows="6" class="w-full border border-outline-variant rounded px-4 py-3 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="c-name" class="block text-label-sm font-bold mb-2">Name *</label>
                                    <input id="c-name" type="text" class="w-full border border-outline-variant rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none">
                                </div>
                                <div>
                                    <label for="c-email" class="block text-label-sm font-bold mb-2">Email *</label>
                                    <input id="c-email" type="email" class="w-full border border-outline-variant rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none">
                                </div>
                            </div>
                            <div>
                                <label for="c-website" class="block text-label-sm font-bold mb-2">Website</label>
                                <input id="c-website" type="url" class="w-full border border-outline-variant rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none">
                            </div>
                            <label class="flex items-start gap-3 text-body-base text-on-surface-variant">
                                <input type="checkbox" class="mt-1 rounded border-outline-variant accent-primary-container">
                                Save my name, email, and website in this browser for the next time I comment.
                            </label>
                            <button type="submit" class="bg-primary-container text-on-primary-container font-bold h-12 px-10 rounded-full hover:brightness-95 transition-all uppercase tracking-wide">Post Comment</button>
                        </form>
                    </div>
                </article>

                {{-- Sidebar --}}
                <aside class="w-full lg:w-80 shrink-0">
                    <x-storefront.blog-sidebar :categories="$categories" :recentPosts="$recentPosts" :tags="$tags" />
                </aside>
            </div>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns :columns="[
        ['title' => 'Featured Products', 'items' => $featured, 'rating' => null],
        ['title' => 'Top Selling Products', 'items' => $topSelling, 'rating' => null],
        ['title' => 'On-sale Products', 'items' => $onSale, 'rating' => 5],
    ]" />
@endsection
