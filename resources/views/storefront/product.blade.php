@extends('layouts.storefront')

@section('title', $product['name'] . ' — ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product['short_description'] ?: ($product['description'] ?: ($product['features'][0] ?? $product['name']))), 155))
@section('og_type', 'product')
@section('og_image', $product['og_image'] ?? $product['image'])

@php
    $currency = setting('general', 'currency', 'PKR');
    $productSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'image' => $product['gallery'] ?: [$product['image']],
        'description' => \Illuminate\Support\Str::limit(strip_tags($product['description'] ?: ($product['short_description'] ?: $product['name'])), 500),
        'sku' => $product['sku'] ?: null,
        'category' => $product['category'] ?: null,
        'offers' => array_filter([
            '@type' => 'Offer',
            'url' => $product['url'],
            'priceCurrency' => $currency,
            'price' => number_format((float) $product['price'], 2, '.', ''),
            'availability' => 'https://schema.org/' . (($product['availability'] ?? '') === 'In stock' ? 'InStock' : 'OutOfStock'),
        ]),
    ]);
    if ((int) ($product['reviews_count'] ?? 0) > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $product['avg_rating'],
            'reviewCount' => (int) $product['reviews_count'],
        ];
    }
    $productBreadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => route('shop')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $product['name'], 'item' => $product['url']],
        ],
    ];
@endphp

@push('schema')
    <script type="application/ld+json">@json($productSchema)</script>
    <script type="application/ld+json">@json($productBreadcrumb)</script>
@endpush

@php
    $isOnSale = $product['compare'] !== null && (float) $product['compare'] > (float) $product['price'];
    $rating = (int) ($product['rating'] ?? 0);
    // Flatten the grouped specs into a single key/value list for the 2-column table.
    $specRows = [];
    foreach (($product['specifications'] ?? []) as $group) {
        foreach ($group as $label => $value) {
            $specRows[] = [$label, $value];
        }
    }
    $specHalf = (int) ceil(count($specRows) / 2);
@endphp

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            {{-- Breadcrumbs --}}
            <nav class="flex flex-wrap items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Shop</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface line-clamp-1">{{ $product['name'] }}</span>
            </nav>

            {{-- ===================== Product hero card ===================== --}}
            {{-- 2 columns from md so tablets get image + buy box side by side; a
                 full-width square gallery below md pushed add-to-cart offscreen. --}}
            <section class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 mb-12 bg-white p-6 lg:p-8 rounded-lg shadow-sm border border-outline-variant"
                x-data="productDetail({
                    gallery: @js($product['gallery']),
                    matrix: @js($variantMatrix),
                    groups: @js($variantOptions),
                    initial: @js($selectedVariant),
                    fallback: { price: @js((float) $product['price']), compare: @js($product['compare']), sku: @js($product['sku'] ?? ''), stock: @js((float) $product['stock']) },
                    tracked: @js((bool) ($product['tracked'] ?? true)),
                    video: @js($product['video'] ?? null),
                })">
                {{-- Gallery: main image (or embedded product video) on top, thumbnails below --}}
                <div class="space-y-6">
                    <div class="relative aspect-square bg-white rounded-lg overflow-hidden flex items-center justify-center group"
                        :class="showVideo ? 'bg-black' : 'p-6 sm:p-8 cursor-zoom-in'">
                        <template x-if="showVideo">
                            <iframe :src="video.embed + '?rel=0&autoplay=1'" class="w-full h-full" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen title="{{ $product['name'] }} — video"></iframe>
                        </template>
                        <template x-if="! showVideo">
                            <img :src="gallery[active]" alt="{{ $product['name'] }}"
                                class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
                        </template>
                        {{-- Brand watermark over the main image (hidden while the video plays; never blocks the zoom) --}}
                        <div x-show="! showVideo" aria-hidden="true"
                            class="pointer-events-none select-none absolute inset-0 flex items-center justify-center">
                            <span style="font-weight:700; color:rgba(0,0,0,0.10); letter-spacing:0.5em; font-size:clamp(1.5rem,5vw,2.75rem); padding-left:0.5em; white-space:nowrap;">Kingway</span>
                        </div>
                    </div>
                    <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-2 no-scrollbar">
                        @foreach ($product['gallery'] as $i => $img)
                            <button type="button" @click="active = {{ $i }}; showVideo = false" aria-label="View image {{ $i + 1 }}"
                                class="w-20 h-20 shrink-0 border-2 rounded-lg p-2 transition-colors"
                                :class="active === {{ $i }} && ! showVideo ? 'border-primary' : 'border-outline-variant hover:border-primary'">
                                <img src="{{ $img }}" alt="" loading="lazy" class="w-full h-full object-contain">
                            </button>
                        @endforeach
                        @if (! empty($product['video']))
                            {{-- Product video tile: provider thumbnail (YouTube) + play badge --}}
                            <button type="button" @click="showVideo = true" aria-label="Play product video"
                                class="relative w-20 h-20 shrink-0 border-2 rounded-lg overflow-hidden transition-colors"
                                :class="showVideo ? 'border-primary' : 'border-outline-variant hover:border-primary'">
                                @if ($product['video']['thumb'])
                                    <img src="{{ $product['video']['thumb'] }}" alt="Product video" loading="lazy" class="w-full h-full object-cover">
                                @else
                                    <span class="absolute inset-0 bg-inverse-surface"></span>
                                @endif
                                <span class="absolute inset-0 grid place-items-center bg-black/25">
                                    <span class="w-8 h-8 rounded-full bg-white/95 shadow grid place-items-center">
                                        <span class="material-symbols-outlined text-[20px] text-error" style="font-variation-settings:'FILL' 1;">play_arrow</span>
                                    </span>
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Product info --}}
                <div class="flex flex-col">
                    <a href="{{ route('shop') }}" class="text-primary font-bold text-label-sm uppercase tracking-wider mb-2">{{ $product['categories'] }}</a>
                    <h1 class="text-headline-md font-medium mb-4">{{ $product['name'] }}</h1>

                    {{-- Rating --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex text-primary-container">
                            @for ($s = 1; $s <= 5; $s++)
                                <span class="material-symbols-outlined text-[20px]" @if ($s <= $rating) style="font-variation-settings: 'FILL' 1;" @endif>star</span>
                            @endfor
                        </div>
                        <span class="text-label-sm text-on-surface-variant">({{ $product['reviews_count'] ?? 0 }} Customer Reviews)</span>
                    </div>

                    {{-- Feature box + SKU --}}
                    <div class="bg-surface p-4 rounded-lg mb-6 text-body-base border border-outline-variant">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($product['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-on-surface-variant italic">SKU: <span x-text="sku || 'N/A'"></span></p>
                    </div>

                    {{-- Variant selector (colour / size / …) --}}
                    @if (! empty($variantOptions))
                        <div class="border-t border-outline-variant pt-6 space-y-5">
                            @foreach ($variantOptions as $group)
                                @php $isColor = collect($group['values'])->contains(fn ($v) => $v['color_hex'] || $v['image']); @endphp
                                <div>
                                    <div class="text-label-sm font-bold text-on-surface-variant mb-2">
                                        {{ $group['name'] }}<span class="font-normal ml-1" x-text="selectedLabel({{ $group['id'] }})"></span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($group['values'] as $v)
                                            @if ($isColor)
                                                <button type="button" @click="pick({{ $group['id'] }}, {{ $v['id'] }})"
                                                    title="{{ $v['label'] }}" aria-label="{{ $v['label'] }}"
                                                    :class="isOn({{ $group['id'] }}, {{ $v['id'] }}) ? 'ring-2 ring-primary ring-offset-2' : 'ring-1 ring-outline-variant hover:ring-primary'"
                                                    class="w-9 h-9 rounded-full overflow-hidden bg-surface grid place-items-center cursor-pointer transition">
                                                    @if ($v['image'])
                                                        <img src="{{ $v['image'] }}" alt="{{ $v['label'] }}" class="w-full h-full object-cover">
                                                    @else
                                                        <span class="w-full h-full block" style="background-color: {{ $v['color_hex'] ?: '#cccccc' }}"></span>
                                                    @endif
                                                </button>
                                            @else
                                                <button type="button" @click="pick({{ $group['id'] }}, {{ $v['id'] }})"
                                                    :class="isOn({{ $group['id'] }}, {{ $v['id'] }}) ? 'border-primary bg-primary-container text-on-primary-container' : 'border-outline-variant hover:border-primary text-on-surface'"
                                                    class="min-w-11 px-3 h-10 rounded-lg border text-sm font-semibold cursor-pointer transition">{{ $v['label'] }}</button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Availability + price + actions --}}
                    @php $tracked = (bool) ($product['tracked'] ?? true); $availExpr = $tracked ? 'stock > 0' : 'true'; @endphp
                    <div class="border-t border-outline-variant pt-6 mt-6">
                        <div class="text-label-sm font-bold mb-2" :class="{{ $availExpr }} ? 'text-green-600' : 'text-error'">
                            @if ($tracked)
                                <span x-show="stock > 0">Availability: <span x-text="stock"></span> in stock</span>
                                <span x-show="stock <= 0" x-cloak>Out of stock</span>
                            @else
                                <span>In stock</span>
                            @endif
                        </div>
                        <div class="flex items-end gap-3 mb-6">
                            <span class="text-[40px] leading-none font-black" :class="onSale ? 'text-error' : 'text-on-surface'">Rs <span x-text="money(price)"></span></span>
                            <template x-if="onSale">
                                <span class="text-xl text-on-surface-variant line-through pb-1">Rs <span x-text="money(compare)"></span></span>
                            </template>
                        </div>

                        @if ($product['variant_id'])
                            <form method="POST" action="{{ route('cart.add') }}" class="flex flex-wrap gap-4 items-center">
                                @csrf
                                <input type="hidden" name="variant_id" :value="variantId">
                                <input type="hidden" name="quantity" :value="qty">
                                <div class="flex border border-outline rounded-lg overflow-hidden h-12">
                                    <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity" class="px-4 hover:bg-surface transition-colors">&minus;</button>
                                    <input type="number" min="1" :max="maxQty" x-model.number="qty" @change="qty = clampQty(qty)" aria-label="Quantity"
                                        class="w-12 text-center border-none focus:ring-0 outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="button" @click="qty = clampQty(qty + 1)" aria-label="Increase quantity" class="px-4 hover:bg-surface transition-colors">+</button>
                                </div>
                                <button type="submit" :class="!({{ $availExpr }}) ? 'opacity-50 pointer-events-none' : ''"
                                    class="bg-primary-container text-on-primary-container px-10 h-12 font-bold rounded hover:brightness-95 transition-all flex items-center gap-2">
                                    <span class="material-symbols-outlined">shopping_cart</span>
                                    <span x-text="({{ $availExpr }}) ? 'Add to Cart' : 'Out of stock'"></span>
                                </button>
                            </form>
                        @endif

                        <button type="button" class="w-full mt-4 bg-[#00d084] text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                            Pay with <span class="font-black italic">link</span>
                        </button>

                        <div class="flex gap-8 mt-6 text-label-sm font-bold text-on-surface-variant">
                            <form method="POST" action="{{ route('wishlist.toggle', $product['slug']) }}">
                                @csrf
                                <button type="submit" @class(['flex items-center gap-1 hover:text-primary transition-colors', 'text-primary' => app(\App\Services\WishlistService::class)->has($product['id'])])>
                                    <span class="material-symbols-outlined text-[18px]" @style(["font-variation-settings: 'FILL' 1" => app(\App\Services\WishlistService::class)->has($product['id'])])>favorite</span>
                                    {{ app(\App\Services\WishlistService::class)->has($product['id']) ? 'In wishlist' : 'Wishlist' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('compare.toggle', $product['slug']) }}">
                                @csrf
                                <button type="submit" @class(['flex items-center gap-1 hover:text-primary transition-colors', 'text-primary' => app(\App\Services\CompareService::class)->has($product['id'])])>
                                    <span class="material-symbols-outlined text-[18px]">sync</span>
                                    {{ app(\App\Services\CompareService::class)->has($product['id']) ? 'In compare' : 'Compare' }}
                                </button>
                            </form>

                            {{-- Share: native OS sheet on mobile, a menu of links on desktop. --}}
                            <div class="relative" x-data="{
                                    open: false,
                                    copied: false,
                                    url: @js(route('product.show', $product['slug'])),
                                    title: @js($product['name']),
                                    text: @js('Check out ' . $product['name']),
                                    toggle() {
                                        if (navigator.share) { navigator.share({ title: this.title, text: this.text, url: this.url }).catch(() => {}); return; }
                                        this.open = ! this.open;
                                    },
                                    copy() {
                                        const ok = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
                                        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(this.url).then(ok).catch(() => {}); return; }
                                        try { const t = document.createElement('textarea'); t.value = this.url; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); ok(); } catch (e) {}
                                    },
                                }" @keydown.escape.window="open = false">
                                <button type="button" @click="toggle()" class="flex items-center gap-1 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">share</span> Share
                                </button>

                                <div x-show="open" x-cloak @click.outside="open = false" x-transition
                                    class="absolute left-0 top-full mt-2 z-20 w-60 max-w-[calc(100vw-2rem)] bg-white border border-outline-variant rounded-xl shadow-xl p-2 font-normal normal-case text-on-surface">
                                    <p class="px-2 pt-1 pb-1.5 text-[11px] font-bold uppercase tracking-wide text-outline">Share this product</p>
                                    <a :href="'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url)" target="_blank" rel="noopener" @click="open = false"
                                        class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
                                        <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="#25D366"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413z"/></svg>
                                        <span class="text-sm">WhatsApp</span>
                                    </a>
                                    <a :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)" target="_blank" rel="noopener" @click="open = false"
                                        class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
                                        <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        <span class="text-sm">Facebook</span>
                                    </a>
                                    <a :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(url)" target="_blank" rel="noopener" @click="open = false"
                                        class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
                                        <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 mx-0.5" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        <span class="text-sm">X (Twitter)</span>
                                    </a>
                                    <a :href="'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(text + ' ' + url)" @click="open = false"
                                        class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
                                        <span class="material-symbols-outlined text-[20px] text-on-surface-variant mx-0.5">mail</span>
                                        <span class="text-sm">Email</span>
                                    </a>
                                    <button type="button" @click="copy()"
                                        class="w-full flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
                                        <span class="material-symbols-outlined text-[20px] mx-0.5" :class="copied ? 'text-primary' : 'text-on-surface-variant'" x-text="copied ? 'check_circle' : 'link'"></span>
                                        <span class="text-sm" x-text="copied ? 'Link copied!' : 'Copy link'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== Tabbed content ===================== --}}
            <section class="bg-white rounded-lg border border-outline-variant mb-12 overflow-hidden" x-data="{ tab: '{{ $errors->any() || session('review_status') ? 'reviews' : 'description' }}' }">
                <div class="border-b border-outline-variant flex justify-start lg:justify-center gap-8 lg:gap-12 px-4 overflow-x-auto no-scrollbar font-bold text-label-sm uppercase tracking-wide">
                    @foreach (['accessories' => 'Accessories', 'description' => 'Description', 'specification' => 'Specification', 'reviews' => 'Reviews', 'more' => 'More Products'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                            class="py-4 px-1 border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}' ? 'border-primary text-on-surface' : 'border-transparent text-on-surface-variant hover:text-primary'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Description --}}
                <div x-show="tab === 'description'" class="p-6 lg:p-12">
                    @if (! empty($product['short_description']))
                        <p class="text-headline-sm font-light text-on-surface-variant max-w-3xl mx-auto text-center mb-10">{{ $product['short_description'] }}</p>
                    @endif
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                        <div class="leading-relaxed text-on-surface-variant space-y-4">
                            @if (! empty($product['description']))
                                {!! nl2br(e($product['description'])) !!}
                            @else
                                <p>No description has been added for this product yet.</p>
                            @endif
                        </div>
                        <div class="w-full h-80 bg-surface rounded-xl overflow-hidden flex items-center justify-center">
                            <img src="{{ $product['gallery'][1] ?? $product['gallery'][0] }}" alt="{{ $product['name'] }}" class="w-full h-full object-contain p-8">
                        </div>
                    </div>
                    @if (! empty($product['features']))
                        <div class="mt-12 max-w-3xl mx-auto">
                            <h3 class="text-headline-sm mb-4">Key features</h3>
                            <ul class="grid sm:grid-cols-2 gap-x-8 gap-y-2 list-disc list-inside text-on-surface-variant">
                                @foreach ($product['features'] as $feature)<li>{{ $feature }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                    @if (! empty($product['warranty']))
                        <div class="mt-10 max-w-3xl mx-auto p-4 bg-surface rounded-lg border border-outline-variant flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">verified_user</span>
                            <span class="text-on-surface-variant">{{ $product['warranty'] }}</span>
                        </div>
                    @endif
                </div>

                {{-- Accessories --}}
                <div x-show="tab === 'accessories'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach ($accessories as $item)
                            <x-storefront.product-card-grid :product="$item" />
                        @endforeach
                    </div>
                </div>

                {{-- Specification --}}
                <div x-show="tab === 'specification'" x-cloak class="p-6 lg:p-12">
                    <h2 class="text-headline-md mb-8 text-center">Technical Specifications</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 border-t border-outline-variant max-w-4xl mx-auto">
                        <div class="divide-y divide-outline-variant">
                            @foreach (array_slice($specRows, 0, $specHalf) as [$label, $value])
                                <div class="py-4 flex justify-between gap-4">
                                    <span class="font-bold">{{ $label }}</span>
                                    <span class="text-on-surface-variant text-right">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="divide-y divide-outline-variant md:border-t-0 border-t border-outline-variant">
                            @foreach (array_slice($specRows, $specHalf) as [$label, $value])
                                <div class="py-4 flex justify-between gap-4">
                                    <span class="font-bold">{{ $label }}</span>
                                    <span class="text-on-surface-variant text-right">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Reviews --}}
                <div x-show="tab === 'reviews'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        {{-- Summary --}}
                        <div>
                            <h2 class="text-headline-md mb-8">Based on {{ $product['reviews_count'] }} review{{ $product['reviews_count'] === 1 ? '' : 's' }}</h2>
                            @if ($product['reviews_count'])
                                @php $byStar = $reviews->groupBy('rating'); @endphp
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="text-[64px] font-black leading-none">{{ number_format($product['avg_rating'], 1) }}</div>
                                    <div>
                                        <div class="text-on-surface-variant">overall</div>
                                        <div class="flex text-primary-container">
                                            @for ($s = 1; $s <= 5; $s++)<span class="material-symbols-outlined" @if ($s <= round($product['avg_rating'])) style="font-variation-settings: 'FILL' 1;" @endif>star</span>@endfor
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2 mb-10">
                                    @for ($star = 5; $star >= 1; $star--)
                                        @php $n = $byStar->get($star)?->count() ?? 0; $pct = $product['reviews_count'] ? round($n / $product['reviews_count'] * 100) : 0; @endphp
                                        <div class="flex items-center gap-4">
                                            <div class="text-primary-container text-label-sm w-24 shrink-0">{{ str_repeat('★', $star) }}{{ str_repeat('☆', 5 - $star) }}</div>
                                            <div class="flex-1 h-2 bg-surface rounded-full overflow-hidden"><div class="h-full bg-primary-container" style="width: {{ $pct }}%"></div></div>
                                            <span class="text-label-sm w-4 text-right">{{ $n }}</span>
                                        </div>
                                    @endfor
                                </div>
                                <div class="space-y-6">
                                    @foreach ($reviews as $review)
                                        <div class="border-b border-outline-variant pb-6 last:border-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-bold">{{ $review->user?->name ?? 'Customer' }}</span>
                                                <span class="text-label-sm text-on-surface-variant">{{ $review->created_at?->format('d M Y') }}</span>
                                            </div>
                                            <div class="flex text-primary-container mb-2">
                                                @for ($s = 1; $s <= 5; $s++)<span class="material-symbols-outlined text-[16px]" @if ($s <= $review->rating) style="font-variation-settings: 'FILL' 1;" @endif>star</span>@endfor
                                            </div>
                                            @if ($review->title)<p class="font-semibold">{{ $review->title }}</p>@endif
                                            <p class="text-on-surface-variant">{{ $review->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-4 p-4 bg-primary-container/10 border border-primary-container rounded-lg text-label-sm">
                                    There are no reviews yet. Be the first to review this product.
                                </div>
                            @endif
                        </div>

                        {{-- Form --}}
                        <div>
                            <h2 class="text-headline-md mb-4">{{ $userReview ? 'Update your review' : 'Write a review' }}</h2>

                            @if (session('review_status'))
                                <div class="mb-6 p-4 rounded bg-secondary-container/40 text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-secondary">check_circle</span> {{ session('review_status') }}
                                </div>
                            @elseif ($userReview && ! $userReview->is_approved)
                                <div class="mb-6 p-4 rounded bg-primary-container/20 border border-primary-container text-label-sm">
                                    Your review is awaiting approval. You can update it below.
                                </div>
                            @endif

                            @auth
                                <form method="POST" action="{{ route('product.reviews.store', $product['slug']) }}" class="space-y-6"
                                    x-data="{ rating: {{ (int) old('rating', $userReview->rating ?? 0) }} }">
                                    @csrf
                                    <div>
                                        <label class="block text-label-sm font-bold mb-2">Your Rating *</label>
                                        <input type="hidden" name="rating" :value="rating">
                                        <div class="flex text-2xl">
                                            @for ($s = 1; $s <= 5; $s++)
                                                <button type="button" @click="rating = {{ $s }}" aria-label="Rate {{ $s }}"
                                                    class="cursor-pointer" :class="rating >= {{ $s }} ? 'text-primary-container' : 'text-outline'">
                                                    <span class="material-symbols-outlined" :style="rating >= {{ $s }} ? `font-variation-settings: 'FILL' 1` : ''">star</span>
                                                </button>
                                            @endfor
                                        </div>
                                        @error('rating')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="review-title" class="block text-label-sm font-bold mb-2">Title</label>
                                        <input id="review-title" name="title" type="text" maxlength="255" value="{{ old('title', $userReview->title ?? '') }}"
                                            class="w-full border border-outline-variant rounded px-4 py-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="review-body" class="block text-label-sm font-bold mb-2">Your Review *</label>
                                        <textarea id="review-body" name="body" rows="5"
                                            class="w-full border border-outline-variant rounded px-4 py-2 focus:ring-primary focus:border-primary">{{ old('body', $userReview->body ?? '') }}</textarea>
                                        @error('body')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <button type="submit" class="bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded-full hover:brightness-95 transition-all">
                                        {{ $userReview ? 'Update review' : 'Submit review' }}
                                    </button>
                                </form>
                            @else
                                <p class="text-on-surface-variant">Please <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">log in</a> to write a review.</p>
                            @endauth
                        </div>
                    </div>
                </div>

                {{-- More Products --}}
                <div x-show="tab === 'more'" x-cloak class="p-6 lg:p-12">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach ($moreProducts as $item)
                            <x-storefront.product-card-grid :product="$item" />
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===================== Related products ===================== --}}
            <section class="mb-12">
                <h2 class="text-headline-md mb-8 pb-3 border-b-2 border-primary-container w-max">Related products</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                    @foreach ($related as $item)
                        <x-storefront.product-card-grid :product="$item" />
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <x-storefront.brand-strip />

    <x-storefront.product-columns />

    {{-- Floating share button, stacked above the support-chat bubble. --}}
    <x-storefront.share-fab :url="route('product.show', $product['slug'])" :title="$product['name']" />
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            // Reactive product buy-box: variant picker + gallery + price/stock/cart.
            Alpine.data('productDetail', (cfg) => ({
                gallery: cfg.gallery,
                matrix: cfg.matrix || [],
                groups: cfg.groups || [],
                selected: {},
                active: 0,
                qty: 1,
                video: cfg.video || null,
                showVideo: false,
                init() {
                    const start = this.matrix.find(v => v.id === cfg.initial) || this.matrix[0];
                    if (start) this.selected = { ...start.options };
                    this.syncImage();
                },
                // The variant matching every currently-selected option.
                get current() {
                    if (! this.matrix.length) return null;
                    return this.matrix.find(v => this.groups.every(g => v.options[g.id] === this.selected[g.id])) || null;
                },
                isOn(gid, vid) { return this.selected[gid] === vid; },
                pick(gid, vid) {
                    this.selected[gid] = vid;
                    // If that exact combination doesn't exist, snap to a variant that has this value.
                    if (! this.current) {
                        const v = this.matrix.find(x => x.options[gid] === vid);
                        if (v) this.selected = { ...v.options };
                    }
                    this.syncImage();
                    this.showVideo = false; // picking a variant returns to its image
                    this.qty = this.clampQty(this.qty); // new variant may have less stock
                },
                // Highest quantity that can be ordered: the variant's stock when
                // tracked, otherwise effectively unlimited (dropship).
                get maxQty() {
                    if (! cfg.tracked) return 999;
                    return Math.max(1, Math.floor(Number(this.stock) || 0));
                },
                clampQty(v) {
                    v = Math.floor(Number(v) || 1);
                    return Math.max(1, Math.min(v, this.maxQty));
                },
                selectedLabel(gid) {
                    const g = this.groups.find(x => x.id === gid);
                    if (! g) return '';
                    const val = g.values.find(v => v.id === this.selected[gid]);
                    return val ? ('· ' + val.label) : '';
                },
                syncImage() {
                    const img = this.current && this.current.image ? this.current.image : null;
                    if (img) {
                        const i = this.gallery.indexOf(img);
                        if (i >= 0) this.active = i;
                    }
                },
                get price() { return this.current ? this.current.price : cfg.fallback.price; },
                get compare() { return this.current ? this.current.compare : cfg.fallback.compare; },
                get onSale() { return this.compare && Number(this.compare) > Number(this.price); },
                get sku() { return this.current ? this.current.sku : cfg.fallback.sku; },
                get stock() { return this.current ? this.current.stock : cfg.fallback.stock; },
                get variantId() { return this.current ? this.current.id : cfg.initial; },
                money(n) { return Number(n || 0).toLocaleString(); },
            }));
        });
    </script>
@endpush
