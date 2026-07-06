@php
    // Real, active brands that have at least one web-listed product. Shows the
    // brand logo when set, otherwise a wordmark; each links to its shop listing.
    $brands = \App\Models\Brand::query()
        ->where('is_active', true)
        ->with('logo:id,disk,path')
        ->withCount(['products' => fn ($q) => $q->webListed()])
        ->orderBy('name')
        ->get()
        ->where('products_count', '>', 0)
        ->take(12);
@endphp

@if ($brands->isNotEmpty())
    {{-- Brand / partner logo strip. --}}
    <section class="py-12 bg-white border-t border-outline-variant">
        <div class="app-container">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-6 gap-y-8 items-center">
                @foreach ($brands as $brand)
                    <a href="{{ route('shop', ['brand' => $brand->slug]) }}" title="{{ $brand->name }}"
                        class="flex justify-center opacity-60 hover:opacity-100 transition-opacity">
                        @if ($brand->logo?->url)
                            <img src="{{ $brand->logo->url }}" alt="{{ $brand->name }}" loading="lazy"
                                class="h-8 w-auto object-contain">
                        @else
                            <span class="text-lg sm:text-xl lg:text-headline-md font-bold tracking-tight text-on-surface text-center whitespace-nowrap">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
