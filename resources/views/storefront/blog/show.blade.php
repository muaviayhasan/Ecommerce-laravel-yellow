@extends('layouts.storefront')

@section('title', $post['title'] . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post['excerpt'] ?? $post['title']), 155))
@section('og_type', 'article')
@section('og_image', $post['image'])

@php
    $articleSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => \Illuminate\Support\Str::limit($post['title'], 110),
        'image' => $post['image'] ? [$post['image']] : null,
        'datePublished' => $post['published_iso'] ?? null,
        'dateModified' => $post['updated_iso'] ?? ($post['published_iso'] ?? null),
        'author' => ['@type' => 'Person', 'name' => $post['author'] ?? config('app.name')],
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
        'mainEntityOfPage' => $post['url'],
        'description' => \Illuminate\Support\Str::limit(strip_tags($post['excerpt'] ?? $post['title']), 300),
    ]);
    $blogBreadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $post['url']],
        ],
    ];
@endphp

@push('schema')
    <script type="application/ld+json">@json($articleSchema)</script>
    <script type="application/ld+json">@json($blogBreadcrumb)</script>
@endpush

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
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-label-sm text-on-surface-variant mb-8">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">person</span> {{ $post['author'] }}</span>
                        <span class="text-outline-variant">|</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_today</span> {{ $post['date'] }}</span>
                        <span class="text-outline-variant">|</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> {{ $post['reading_time'] }} min read</span>
                        @if (! empty($postCategories))
                            <span class="text-outline-variant">|</span>
                            <span class="flex flex-wrap items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">folder</span>
                                @foreach ($postCategories as $c)
                                    <a href="{{ route('blog', ['category' => $c['slug']]) }}" class="font-medium hover:text-primary transition-colors">{{ $c['name'] }}</a>{{ ! $loop->last ? ',' : '' }}
                                @endforeach
                            </span>
                        @endif
                    </div>

                    @php $body = (string) ($post['body'] ?? ''); $looksHtml = (bool) preg_match('/<[a-z]/i', $body); @endphp
                    <div class="text-body-base text-on-surface-variant space-y-6 leading-relaxed prose max-w-none">
                        @if ($looksHtml)
                            {!! $body !!}
                        @else
                            {!! nl2br(e($body)) !!}
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if (! empty($postTags))
                        <div class="mt-10 pt-6 border-t border-outline-variant flex flex-wrap items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">sell</span>
                            <span class="text-label-sm font-bold text-on-surface-variant mr-1">Tags:</span>
                            @foreach ($postTags as $t)
                                <a href="{{ route('blog', ['tag' => $t['slug']]) }}"
                                    class="px-3 py-1 border border-outline-variant text-[12px] rounded hover:bg-primary-container hover:text-on-primary-container transition-colors">{{ $t['name'] }}</a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Author --}}
                    <div class="mt-12 p-6 lg:p-8 bg-surface-container border border-outline-variant rounded-lg flex flex-col sm:flex-row gap-6 items-start">
                        <div class="w-16 h-16 rounded-full bg-primary-container text-on-primary-container grid place-items-center text-2xl font-bold shrink-0">
                            {{ strtoupper(substr($post['author'] ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-label-sm text-on-surface-variant uppercase tracking-wide">Written by</p>
                            <h3 class="text-headline-md font-medium mb-2 capitalize">{{ $post['author'] ?? 'admin' }}</h3>
                            <p class="text-body-base text-on-surface-variant leading-relaxed">
                                Part of the {{ config('app.name') }} team — sharing practical advice on choosing, using
                                and maintaining home appliances so you get the best value for your home.
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
                        {{-- Existing comments --}}
                        @if ($commentsCount > 0)
                            <h2 class="text-headline-md font-bold mb-6">{{ $commentsCount }} {{ \Illuminate\Support\Str::plural('Comment', $commentsCount) }}</h2>
                            <div class="space-y-5 mb-12">
                                @foreach ($comments as $comment)
                                    <div class="flex gap-4">
                                        <div class="w-11 h-11 rounded-full bg-primary-container text-on-primary-container grid place-items-center font-bold shrink-0">{{ strtoupper(substr($comment->name, 0, 1)) }}</div>
                                        <div class="flex-1 min-w-0 space-y-3">
                                            <div class="bg-surface-container-low border border-outline-variant rounded-lg p-4">
                                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                                    <span class="font-bold text-on-surface">
                                                        @if ($comment->website)
                                                            <a href="{{ $comment->website }}" target="_blank" rel="noopener nofollow" class="hover:text-primary transition-colors">{{ $comment->name }}</a>
                                                        @else
                                                            {{ $comment->name }}
                                                        @endif
                                                    </span>
                                                    <span class="text-label-sm text-outline">{{ $comment->created_at->format('M j, Y') }}</span>
                                                </div>
                                                <p class="text-body-base text-on-surface-variant whitespace-pre-line break-words">{{ $comment->body }}</p>
                                            </div>

                                            {{-- Staff / threaded replies --}}
                                            @foreach ($comment->replies as $reply)
                                                <div class="flex gap-3 ml-4 sm:ml-8">
                                                    <div class="w-9 h-9 rounded-full grid place-items-center font-bold shrink-0 {{ $reply->is_admin ? 'bg-inverse-surface text-inverse-on-surface' : 'bg-primary-container text-on-primary-container' }}">{{ strtoupper(substr($reply->name, 0, 1)) }}</div>
                                                    <div class="flex-1 min-w-0 bg-white border border-outline-variant rounded-lg p-4">
                                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                                            <span class="font-bold text-on-surface">{{ $reply->name }}</span>
                                                            @if ($reply->is_admin)
                                                                <span class="px-2 py-0.5 bg-primary-container text-on-primary-container text-[10px] font-bold rounded-full uppercase tracking-wide">Staff</span>
                                                            @endif
                                                            <span class="text-label-sm text-outline">{{ $reply->created_at->format('M j, Y') }}</span>
                                                        </div>
                                                        <p class="text-body-base text-on-surface-variant whitespace-pre-line break-words">{{ $reply->body }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <h2 class="text-headline-md font-bold mb-4">Leave a Reply</h2>
                        <p class="text-body-base text-on-surface-variant mb-6">Your email address will not be published. Required fields are marked *</p>

                        @if (session('comment_status'))
                            <div class="mb-6 flex items-center gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg text-body-base">
                                <span class="material-symbols-outlined">check_circle</span> {{ session('comment_status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('blog.comments.store', $post['slug']) }}" class="space-y-6"
                            x-data="commentForm()" @submit="save()">
                            @csrf
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label for="comment" class="block text-label-sm font-bold">Comment *</label>
                                    <span class="text-[11px] text-outline"><span x-text="body.length">0</span>/2000</span>
                                </div>
                                <textarea id="comment" name="body" rows="6" maxlength="2000" required x-model="body"
                                    class="w-full border rounded px-4 py-3 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none @error('body') border-error @else border-outline-variant @enderror">{{ old('body') }}</textarea>
                                @error('body')<p class="mt-1 text-error text-label-sm">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="c-name" class="block text-label-sm font-bold">Name *</label>
                                        <span class="text-[11px] text-outline"><span x-text="name.length">0</span>/80</span>
                                    </div>
                                    <input id="c-name" name="name" type="text" maxlength="80" required x-model="name"
                                        class="w-full border rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none @error('name') border-error @else border-outline-variant @enderror">
                                    @error('name')<p class="mt-1 text-error text-label-sm">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="c-email" class="block text-label-sm font-bold mb-2">Email *</label>
                                    <input id="c-email" name="email" type="email" maxlength="255" required x-model="email"
                                        class="w-full border rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none @error('email') border-error @else border-outline-variant @enderror">
                                    @error('email')<p class="mt-1 text-error text-label-sm">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label for="c-website" class="block text-label-sm font-bold mb-2">Website</label>
                                <input id="c-website" name="website" type="url" maxlength="255" x-model="website" placeholder="https://"
                                    class="w-full border rounded h-11 px-4 bg-white focus:ring-1 focus:ring-primary-container focus:border-primary-container outline-none @error('website') border-error @else border-outline-variant @enderror">
                                @error('website')<p class="mt-1 text-error text-label-sm">{{ $message }}</p>@enderror
                            </div>
                            <label class="flex items-start gap-3 text-body-base text-on-surface-variant">
                                <input type="checkbox" x-model="remember" class="mt-1 rounded border-outline-variant accent-primary-container">
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

    <x-storefront.product-columns />

    @push('scripts')
        <script>
            function commentForm() {
                return {
                    body: @js(old('body', '')),
                    name: @js(old('name', '')),
                    email: @js(old('email', '')),
                    website: @js(old('website', '')),
                    remember: false,
                    init() {
                        // Prefill from a previous comment if the visitor opted in.
                        try {
                            const s = JSON.parse(localStorage.getItem('blogCommenter') || 'null');
                            if (s) {
                                this.name = this.name || s.name || '';
                                this.email = this.email || s.email || '';
                                this.website = this.website || s.website || '';
                                this.remember = true;
                            }
                        } catch (e) {}
                    },
                    save() {
                        try {
                            if (this.remember) {
                                localStorage.setItem('blogCommenter', JSON.stringify({ name: this.name, email: this.email, website: this.website }));
                            } else {
                                localStorage.removeItem('blogCommenter');
                            }
                        } catch (e) {}
                    },
                };
            }
        </script>
    @endpush
@endsection
