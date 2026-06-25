@props([
    'categories' => [],
    'recentPosts' => [],
    'tags' => [],
])

{{-- Shared blog sidebar (search, about, categories, recent posts, tag cloud). --}}
<div class="space-y-8">
    {{-- Search --}}
    <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
        <form action="{{ route('blog') }}" method="GET" class="relative">
            <input type="text" name="q" placeholder="Search blog posts..."
                class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 pr-11 text-body-base focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined">search</span>
            </button>
        </form>
    </div>

    {{-- About --}}
    <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
        <h3 class="text-headline-md font-bold mb-2">About Blog</h3>
        <div class="w-12 h-1 bg-primary-container mb-4"></div>
        <p class="text-on-surface-variant text-body-base leading-relaxed">
            {{ config('app.name') }} Insights is your destination for the latest in consumer electronics, industrial
            design and technological breakthroughs — in-depth analysis on the products that shape our daily lives.
        </p>
    </div>

    {{-- Categories --}}
    <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
        <h3 class="text-headline-md font-bold mb-2">Categories</h3>
        <div class="w-12 h-1 bg-primary-container mb-4"></div>
        <ul class="flex flex-col text-body-base">
            @foreach ($categories as $name => $count)
                <li class="border-b border-outline-variant last:border-0">
                    <a href="{{ route('blog') }}" class="flex items-center justify-between py-3 group hover:text-primary transition-colors">
                        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">chevron_right</span> {{ $name }}</span>
                        <span class="text-on-surface-variant group-hover:text-primary">({{ $count }})</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Recent posts --}}
    <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
        <h3 class="text-headline-md font-bold mb-2">Recent Posts</h3>
        <div class="w-12 h-1 bg-primary-container mb-4"></div>
        <div class="flex flex-col gap-6">
            @foreach ($recentPosts as $rp)
                <a href="{{ data_get($rp, 'url', route('blog')) }}" class="flex gap-4 group">
                    <div class="w-20 h-20 shrink-0 rounded overflow-hidden bg-surface">
                        <img src="{{ data_get($rp, 'image') }}" alt="" loading="lazy" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-product-title group-hover:text-primary transition-colors line-clamp-2">{{ data_get($rp, 'title') }}</h4>
                        <span class="text-on-surface-variant text-[12px]">{{ data_get($rp, 'date') }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tag cloud --}}
    <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
        <h3 class="text-headline-md font-bold mb-2">Tags Cloud</h3>
        <div class="w-12 h-1 bg-primary-container mb-4"></div>
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                <a href="{{ route('blog') }}" class="px-3 py-1 border border-outline-variant text-[12px] rounded hover:bg-primary-container hover:text-on-primary-container transition-colors">{{ $tag }}</a>
            @endforeach
        </div>
    </div>
</div>
