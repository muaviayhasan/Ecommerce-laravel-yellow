@if (!empty($mapsEnabled))
    <div class="space-y-2 pb-1">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input type="text" x-ref="placesSearch" placeholder="Search your address…" autocomplete="off" @keydown.enter.prevent
                class="w-full rounded-lg border border-outline-variant pl-10 pr-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
        </div>
        <button type="button" @click="useMyLocation()"
            class="w-full flex items-center justify-center gap-2 border border-primary text-primary rounded-lg py-2.5 font-bold text-label-sm hover:bg-primary-container/20 transition">
            <span class="material-symbols-outlined text-[18px]">my_location</span> Use my current location
        </button>
        <div x-ref="map" class="w-full h-44 rounded-lg border border-outline-variant bg-surface-container-low overflow-hidden"></div>
        <p class="text-label-sm text-on-surface-variant flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">info</span> Pick from search, tap “use my location”, or drag the pin to your exact spot.
        </p>
    </div>
@endif
