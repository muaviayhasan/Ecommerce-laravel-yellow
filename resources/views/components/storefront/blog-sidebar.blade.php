@props([
    'categories' => [],
    'recentPosts' => [],
    'tags' => [],
    'activeCategory' => null,
    'activeTag' => null,
])

{{-- Shared blog sidebar (search, about, categories, recent posts, newsletter, tags). --}}
<div class="space-y-6">
    {{-- Search — pill style matching the site header search --}}
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
        <form action="{{ route('blog') }}" method="GET"
            class="flex border-2 border-primary-container rounded-full overflow-hidden focus-within:shadow-md transition-shadow">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search blog posts…"
                aria-label="Search blog posts"
                class="flex-1 min-w-0 px-5 py-2.5 border-none outline-none text-body-base bg-surface-bright">
            <button type="submit" aria-label="Search"
                class="bg-primary-container px-5 flex items-center justify-center hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[20px]">search</span>
            </button>
        </form>
    </div>

    {{-- About — dark branded card echoing the page hero --}}
    <div class="relative overflow-hidden bg-inverse-surface text-inverse-on-surface p-6 rounded-xl shadow-sm">
        <div class="pointer-events-none absolute -top-12 -right-12 w-40 h-40 rounded-full bg-primary-container/20 blur-2xl" aria-hidden="true"></div>
        <div class="relative">
            <div class="w-11 h-11 rounded-full bg-primary-container text-on-primary-container grid place-items-center mb-4">
                <span class="material-symbols-outlined">auto_stories</span>
            </div>
            <h3 class="text-headline-md font-bold">About the blog<span class="text-primary-container">.</span></h3>
            <p class="text-inverse-on-surface/80 text-body-base leading-relaxed mt-2">
                Buying guides, reviews and maintenance tips for home appliances — helping you pick the right
                coolers, geysers, fans, washing machines and solar for your home.
            </p>
            <a href="{{ route('shop') }}" class="mt-4 inline-flex items-center gap-1.5 text-primary-container font-semibold text-label-sm hover:underline">
                Browse the store <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
    </div>

    {{-- Categories --}}
    @if (! empty($categories))
        <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
            <h3 class="text-headline-md font-bold mb-2 px-1">Categories</h3>
            <div class="w-12 h-1 bg-primary-container mb-3 ml-1"></div>
            <ul class="flex flex-col gap-1 text-body-base">
                @foreach ($categories as $c)
                    @php $active = $activeCategory === ($c['slug'] ?? null); @endphp
                    <li>
                        <a href="{{ route('blog', ['category' => $c['slug']]) }}"
                            @class([
                                'flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg group transition-colors',
                                'bg-primary-container text-on-primary-container font-semibold' => $active,
                                'hover:bg-surface-container text-on-surface' => ! $active,
                            ])>
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="material-symbols-outlined text-[18px] {{ $active ? '' : 'text-primary group-hover:translate-x-0.5 transition-transform' }}">chevron_right</span>
                                <span class="truncate">{{ $c['name'] }}</span>
                            </span>
                            <span @class([
                                'shrink-0 min-w-7 h-6 px-2 grid place-items-center rounded-full text-[11px] font-bold',
                                'bg-inverse-surface/10 text-on-primary-container' => $active,
                                'bg-surface-container text-on-surface-variant group-hover:bg-primary-container group-hover:text-on-primary-container transition-colors' => ! $active,
                            ])>{{ $c['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Recent posts --}}
    @if (! empty($recentPosts) && count($recentPosts))
        <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
            <h3 class="text-headline-md font-bold mb-2 px-1">Recent Posts</h3>
            <div class="w-12 h-1 bg-primary-container mb-4 ml-1"></div>
            <div class="flex flex-col">
                @foreach ($recentPosts as $rp)
                    <a href="{{ data_get($rp, 'url', route('blog')) }}"
                        class="flex gap-4 items-center group rounded-lg p-2 -mx-1 hover:bg-surface-container transition-colors">
                        <div class="w-20 h-20 shrink-0 rounded-lg overflow-hidden bg-surface border border-outline-variant/50">
                            <img src="{{ data_get($rp, 'image') }}" alt="" loading="lazy"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-product-title font-semibold group-hover:text-primary transition-colors line-clamp-2 leading-snug">{{ data_get($rp, 'title') }}</h4>
                            <span class="mt-1.5 flex items-center gap-1 text-on-surface-variant text-[12px]">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                {{ data_get($rp, 'date') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Newsletter — compact subscribe card --}}
    <div class="bg-primary-container text-on-primary-container p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-[28px]">mark_email_unread</span>
            <h3 class="text-headline-md font-bold leading-tight">Never miss a guide</h3>
        </div>
        <p class="text-body-base mb-4 opacity-90">New buying guides and tips, straight to your inbox.</p>
        @if (session('newsletter_status'))
            <div class="mb-3 flex items-center gap-2 bg-white/70 px-4 py-2.5 rounded-full text-label-sm font-medium">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                {{ session('newsletter_status') }}
            </div>
        @endif
        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col gap-2.5">
            @csrf
            <input name="email" type="email" required placeholder="Your email address" value="{{ old('email') }}"
                aria-label="Email address"
                class="w-full bg-white rounded-full px-5 py-2.5 border-none outline-none text-body-base text-on-surface">
            <button type="submit"
                class="w-full bg-inverse-surface text-inverse-on-surface rounded-full py-2.5 font-bold hover:opacity-90 active:scale-[0.99] transition-all">
                Subscribe
            </button>
        </form>
        @error('email', 'newsletter')
            <p class="mt-2 text-label-sm text-error flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}
            </p>
        @enderror
    </div>

    {{-- Tag cloud --}}
    @if (! empty($tags))
        <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
            <h3 class="text-headline-md font-bold mb-2 px-1">Popular Tags</h3>
            <div class="w-12 h-1 bg-primary-container mb-4 ml-1"></div>
            <div class="flex flex-wrap gap-2">
                @foreach ($tags as $t)
                    @php $active = $activeTag === ($t['slug'] ?? null); @endphp
                    <a href="{{ route('blog', ['tag' => $t['slug']]) }}"
                        @class([
                            'px-3.5 py-1.5 border text-[12px] font-medium rounded-full transition-colors',
                            'bg-primary-container text-on-primary-container border-primary-container font-semibold' => $active,
                            'border-outline-variant text-on-surface-variant hover:bg-primary-container hover:text-on-primary-container hover:border-primary-container' => ! $active,
                        ])><span class="opacity-60">#</span>{{ $t['name'] }}</a>
                @endforeach
            </div>
        </div>
    @endif
</div>
