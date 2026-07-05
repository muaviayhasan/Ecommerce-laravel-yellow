{{-- Floating compare tray: shows the shortlisted products with a jump to the full comparison. --}}
@php
    $compare = app(\App\Services\CompareService::class);
    $compareProducts = $compare->products();
    $compareCount = $compareProducts->count();
    $compareMax = 4;
@endphp

@if ($compareCount > 0)
    <div x-data="{ show: true }" x-show="show" x-init="$store.compareBar.visible = true"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        class="fixed inset-x-0 bottom-16 md:bottom-0 z-50 bg-inverse-surface text-inverse-on-surface shadow-[0_-4px_24px_rgba(0,0,0,0.25)] print:hidden">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3 sm:gap-4">

            {{-- Prompt --}}
            <div class="hidden md:block shrink-0 w-40">
                @if ($compareCount < 2)
                    <p class="text-sm font-semibold leading-snug">Select at least {{ 2 - $compareCount }} more
                        product{{ (2 - $compareCount) > 1 ? 's' : '' }} to compare</p>
                @else
                    <p class="text-sm font-semibold leading-snug">{{ $compareCount }} products selected</p>
                    <p class="text-xs text-inverse-on-surface/60">Ready to compare</p>
                @endif
            </div>

            {{-- Product slots --}}
            <div class="flex-1 flex items-center gap-2.5 overflow-x-auto">
                @foreach ($compareProducts as $p)
                    @php $img = $p->media->first()?->url ?? $p->defaultVariant?->image?->url ?? \App\Support\Storefront::placeholder(); @endphp
                    <div class="relative shrink-0">
                        <a href="{{ route('product.show', $p->slug) }}" title="{{ $p->name }}">
                            <img src="{{ $img }}" alt="{{ $p->name }}"
                                class="w-14 h-14 rounded-lg object-cover bg-white ring-1 ring-white/10">
                        </a>
                        <form method="POST" action="{{ route('compare.remove', $p->slug) }}" class="absolute top-1 right-1">
                            @csrf @method('DELETE')
                            <button type="submit" aria-label="Remove {{ $p->name }} from compare"
                                class="w-5 h-5 grid place-items-center rounded-full bg-white/90 text-on-surface shadow-sm ring-1 ring-black/5 hover:bg-error hover:text-white transition backdrop-blur-sm">
                                <span class="material-symbols-outlined text-[15px] leading-none">close</span>
                            </button>
                        </form>
                    </div>
                @endforeach

                @for ($i = $compareCount; $i < $compareMax; $i++)
                    <div class="shrink-0 w-14 h-14 rounded-lg border-2 border-dashed border-inverse-on-surface/25 grid place-items-center">
                        <span class="material-symbols-outlined text-inverse-on-surface/25 text-[20px]">add</span>
                    </div>
                @endfor
            </div>

            {{-- Actions --}}
            <div class="shrink-0 flex items-center gap-1.5 sm:gap-2">
                @if ($compareCount >= 2)
                    <a href="{{ route('compare') }}"
                        class="px-4 sm:px-5 py-2.5 rounded-full bg-primary-container text-on-primary-container text-sm font-semibold hover:brightness-105 active:scale-95 transition whitespace-nowrap">
                        View comparison
                    </a>
                @else
                    <span class="px-4 sm:px-5 py-2.5 rounded-full bg-white/15 text-inverse-on-surface/50 text-sm font-semibold cursor-not-allowed whitespace-nowrap">
                        View comparison
                    </span>
                @endif

                <form method="POST" action="{{ route('compare.clear') }}" @submit="$store.compareBar.visible = false">
                    @csrf @method('DELETE')
                    <button type="submit" aria-label="Clear all" title="Clear all"
                        class="w-9 h-9 grid place-items-center rounded-full text-inverse-on-surface/70 hover:bg-white/10 hover:text-white transition">
                        <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                    </button>
                </form>

                <button type="button" @click="show = false; $store.compareBar.visible = false" aria-label="Hide compare bar"
                    class="w-9 h-9 grid place-items-center rounded-full text-inverse-on-surface/70 hover:bg-white/10 hover:text-white transition">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        </div>
    </div>
@endif
