{{-- Greyed-out brand/partner logo strip (placeholder wordmarks). --}}
<section class="py-12 bg-white border-t border-outline-variant">
    <div class="app-container">
        <div class="flex flex-wrap items-center justify-between gap-12 opacity-50 hover:opacity-100 transition-opacity">
            @foreach (['airnd', 'coinbuild', 'dirrbble', 'Instagrom', 'NEETFLIX'] as $brand)
                <span class="text-headline-md font-bold tracking-tighter">{{ $brand }}</span>
            @endforeach
        </div>
    </div>
</section>
