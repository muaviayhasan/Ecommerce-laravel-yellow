@extends('layouts.storefront')

@section('title', 'My Addresses — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="addresses">
        <div x-data="addressBook()" class="space-y-6">
            {{-- Header --}}
            <div class="bg-white rounded-lg border border-outline-variant p-5 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold">Addresses</h1>
                    <p class="text-label-sm text-on-surface-variant">Manage your shipping &amp; billing addresses.</p>
                </div>
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 bg-primary-container text-on-primary-container px-4 py-2.5 rounded-full font-bold text-label-sm hover:brightness-105 transition shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span> Add address
                </button>
            </div>

            {{-- List --}}
            @if ($addresses->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">location_off</span>
                    <p class="mt-3 text-lg font-light">You have no saved addresses.</p>
                    <button type="button" @click="openCreate()" class="inline-block mt-5 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded-full hover:brightness-105 transition">Add your first address</button>
                </div>
            @else
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach ($addresses as $a)
                        @php $payload = ['id' => $a->id, 'label' => $a->label ?? '', 'name' => $a->name ?? '', 'phone' => $a->phone ?? '', 'line1' => $a->line1 ?? '', 'line2' => $a->line2 ?? '', 'city' => $a->city ?? '', 'state' => $a->state ?? '', 'zip' => $a->zip ?? '', 'country' => $a->country ?? '', 'latitude' => $a->latitude !== null ? (string) $a->latitude : '', 'longitude' => $a->longitude !== null ? (string) $a->longitude : '', 'is_default_billing' => (bool) $a->is_default_billing, 'is_default_shipping' => (bool) $a->is_default_shipping]; @endphp
                        <div class="bg-white rounded-lg border border-outline-variant p-5 flex flex-col">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                    @if ($a->label)<span class="font-bold truncate">{{ $a->label }}</span>@endif
                                    @if ($a->is_default_shipping)<span class="text-[10px] font-bold uppercase tracking-wide bg-primary-container/40 text-on-primary-container px-2 py-0.5 rounded-full shrink-0">Shipping</span>@endif
                                    @if ($a->is_default_billing)<span class="text-[10px] font-bold uppercase tracking-wide bg-secondary-container/50 text-secondary px-2 py-0.5 rounded-full shrink-0">Billing</span>@endif
                                </div>
                            </div>
                            <p class="font-medium">{{ $a->name }}</p>
                            @if ($a->phone)<p class="text-body-base text-on-surface-variant">{{ $a->phone }}</p>@endif
                            <p class="text-body-base text-on-surface-variant mt-0.5">{{ collect([$a->line1, $a->line2, $a->city, $a->state, $a->zip, $a->country])->filter()->join(', ') }}</p>

                            <div class="flex items-center gap-2 mt-4 pt-3 border-t border-outline-variant/60">
                                <button type="button" @click='openEdit(@json($payload))' class="text-label-sm font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">edit</span> Edit
                                </button>
                                <form method="POST" action="{{ route('account.addresses.destroy', $a) }}" @submit.prevent="if (confirm('Remove this address?')) $el.submit()" class="ms-auto">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-label-sm font-bold text-error hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add / edit modal --}}
            <div x-show="open" x-cloak x-effect="document.documentElement.style.overflow = open ? 'hidden' : ''"
                class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center" style="display:none;">
                <div class="absolute inset-0 bg-black/50" @click="close()"></div>
                <div x-show="open" x-transition
                    class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
                    <div class="shrink-0 bg-white border-b border-outline-variant px-5 py-4 flex items-center justify-between">
                        <h2 class="font-bold text-lg" x-text="form.id ? 'Edit address' : 'Add address'"></h2>
                        <button type="button" @click="close()" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                    </div>

                    <form method="POST" :action="form.id ? `{{ url('account/addresses') }}/${form.id}` : '{{ route('account.addresses.store') }}'" class="flex flex-col min-h-0 flex-1">
                        @csrf
                        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                        <input type="hidden" name="address_id" :value="form.id">

                        {{-- Scrollable fields --}}
                        <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-4 space-y-4">

                        {{-- Maps: search + "use my location" + pin (only when configured) --}}
                        @include('storefront.account.partials.address-map')

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-label-sm font-medium mb-1">Full name <span class="text-error">*</span></label>
                                <input type="text" name="name" x-model="form.name" required autocomplete="name" autocapitalize="words" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('name') border-error @enderror">
                                @error('name')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-label-sm font-medium mb-1">Phone</label>
                                <input type="tel" name="phone" x-model="form.phone" data-mask="phone" maxlength="12" autocomplete="tel" inputmode="tel" placeholder="0300-0000000" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('phone') border-error @enderror">
                                @error('phone')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-label-sm font-medium mb-1">Street address <span class="text-error">*</span></label>
                            <input type="text" name="line1" x-ref="line1" x-model="form.line1" required autocomplete="address-line1" placeholder="House no., street, area" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('line1') border-error @enderror">
                            @error('line1')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            <div x-show="showApt || form.line2" x-transition class="mt-2">
                                <input type="text" name="line2" x-model="form.line2" autocomplete="address-line2" placeholder="Apartment, suite, landmark (optional)" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            </div>
                            <button type="button" x-show="!(showApt || form.line2)" @click="showApt = true" class="mt-1.5 text-label-sm font-bold text-primary hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">add</span> Add apartment / landmark
                            </button>
                        </div>

                        <div>
                            <label class="block text-label-sm font-medium mb-1">City <span class="text-error">*</span></label>
                            <input type="text" name="city" x-model="form.city" required autocomplete="address-level2" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('city') border-error @enderror">
                            @error('city')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Optional details (collapsed by default) --}}
                        <div>
                            <button type="button" x-show="!(showMore || form.state || form.zip)" @click="showMore = true" class="text-label-sm font-bold text-primary hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">add</span> Add postal code / province
                            </button>
                            <div x-show="showMore || form.state || form.zip" x-transition class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-label-sm font-medium mb-1">State / Province</label>
                                    <input type="text" name="state" x-model="form.state" autocomplete="address-level1" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                </div>
                                <div>
                                    <label class="block text-label-sm font-medium mb-1">ZIP / Postal code</label>
                                    <input type="text" name="zip" x-model="form.zip" autocomplete="postal-code" inputmode="numeric" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- Country is fixed by the store; kept as a hidden field so we never ask for it. --}}
                        <input type="hidden" name="country" :value="form.country">
                        <input type="hidden" name="latitude" :value="form.latitude">
                        <input type="hidden" name="longitude" :value="form.longitude">

                        {{-- Label chips --}}
                        <div>
                            <label class="block text-label-sm font-medium mb-1.5">Save this address as</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setLabel('Home')" class="px-4 py-2 rounded-full border text-label-sm font-bold transition inline-flex items-center gap-1" :class="form.label === 'Home' ? 'bg-primary-container text-on-primary-container border-primary' : 'bg-white text-on-surface-variant border-outline-variant hover:border-primary'">🏠 Home</button>
                                <button type="button" @click="setLabel('Office')" class="px-4 py-2 rounded-full border text-label-sm font-bold transition inline-flex items-center gap-1" :class="form.label === 'Office' ? 'bg-primary-container text-on-primary-container border-primary' : 'bg-white text-on-surface-variant border-outline-variant hover:border-primary'">🏢 Office</button>
                                <button type="button" @click="pickOther()" class="px-4 py-2 rounded-full border text-label-sm font-bold transition inline-flex items-center gap-1" :class="labelCustom ? 'bg-primary-container text-on-primary-container border-primary' : 'bg-white text-on-surface-variant border-outline-variant hover:border-primary'">📍 Other</button>
                            </div>
                            <input type="text" x-show="labelCustom" x-model="form.label" maxlength="60" placeholder="Label (e.g. Mum's house)" class="mt-2 w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            <input type="hidden" name="label" :value="form.label">
                        </div>

                        @if ($addresses->isNotEmpty())
                            <div class="flex flex-col gap-2 pt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_default_shipping" value="1" x-model="form.is_default_shipping" class="rounded border-outline-variant text-primary focus:ring-primary">
                                    <span class="text-body-base">Set as default shipping address</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_default_billing" value="1" x-model="form.is_default_billing" class="rounded border-outline-variant text-primary focus:ring-primary">
                                    <span class="text-body-base">Set as default billing address</span>
                                </label>
                            </div>
                        @endif

                        </div>{{-- /scrollable fields --}}

                        {{-- Pinned footer --}}
                        <div class="shrink-0 border-t border-outline-variant p-4 flex items-center gap-3 bg-white">
                            <button type="submit" class="flex-1 bg-primary-container text-on-primary-container py-3 rounded-full font-bold hover:brightness-105 transition">Save address</button>
                            <button type="button" @click="close()" class="px-5 py-3 rounded-full font-bold text-on-surface-variant hover:bg-surface-container transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-storefront.account-shell>

    @if (!empty($mapsEnabled))
        @push('scripts')
            <script>
                window.__initAddressMaps = function () {
                    window.__googleMapsReady = true;
                    window.dispatchEvent(new Event('google-maps-ready'));
                };
            </script>
            <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsKey) }}&libraries=places&callback=__initAddressMaps&loading=async"></script>
        @endpush
    @endif

    @push('scripts')
        <script>
            function addressBook() {
                const DEFAULT_COUNTRY = @js(setting('maps', 'default_country', 'Pakistan'));
                const MAPS_ENABLED = {{ !empty($mapsEnabled) ? 'true' : 'false' }};
                const MAP_CENTER = @js($mapCenter ?? ['lat' => 30.3753, 'lng' => 69.3451]);
                const COUNTRY_CODE = @js($countryCode ?? null);
                const blank = {
                    id: '', label: 'Home',
                    name: @js(auth()->user()->name ?? ''), phone: @js(auth()->user()->phone ?? ''),
                    line1: '', line2: '', city: '', state: '', zip: '', country: DEFAULT_COUNTRY,
                    latitude: '', longitude: '',
                    is_default_billing: false, is_default_shipping: false,
                };
                return {
                    open: {{ $errors->any() ? 'true' : 'false' }},
                    showApt: false, showMore: false, labelCustom: false,
                    map: null, marker: null, geocoder: null, autocomplete: null,
                    form: @js([
                        'id' => old('address_id', ''),
                        'label' => old('label', ''),
                        'name' => old('name', ''),
                        'phone' => old('phone', ''),
                        'line1' => old('line1', ''),
                        'line2' => old('line2', ''),
                        'city' => old('city', ''),
                        'state' => old('state', ''),
                        'zip' => old('zip', ''),
                        'country' => old('country', ''),
                        'latitude' => old('latitude', ''),
                        'longitude' => old('longitude', ''),
                        'is_default_billing' => (bool) old('is_default_billing'),
                        'is_default_shipping' => (bool) old('is_default_shipping'),
                    ]),
                    init() {
                        if (!this.form.country) this.form.country = DEFAULT_COUNTRY;
                        if (!this.form.label) this.form.label = 'Home';
                        this.syncLabelState();
                        this.$watch('open', (v) => v && this.bootMap());
                        if (this.open) this.bootMap();
                    },
                    openCreate() { this.form = { ...blank }; this.reset(); this.open = true; },
                    openEdit(a) { this.form = { ...blank, ...a }; if (!this.form.country) this.form.country = DEFAULT_COUNTRY; this.reset(); this.open = true; },
                    reset() { this.showApt = false; this.showMore = false; this.syncLabelState(); this.$nextTick(() => this.centreMap()); },
                    close() { this.open = false; },

                    // Label chips
                    setLabel(v) { this.form.label = v; this.labelCustom = false; },
                    pickOther() { this.labelCustom = true; if (['Home', 'Office'].includes(this.form.label)) this.form.label = ''; },
                    syncLabelState() { this.labelCustom = !!(this.form.label && !['Home', 'Office'].includes(this.form.label)); },

                    // Google Maps (progressive enhancement — everything below no-ops without a key)
                    bootMap() {
                        if (!MAPS_ENABLED) return;
                        if (!(window.google && window.google.maps)) {
                            window.addEventListener('google-maps-ready', () => this.bootMap(), { once: true });
                            return;
                        }
                        this.$nextTick(() => this.initMap());
                    },
                    initMap() {
                        const el = this.$refs.map;
                        if (!el) return;
                        if (this.map) { this.centreMap(); return; }
                        this.geocoder = new google.maps.Geocoder();
                        const center = this.pinOrDefault();
                        this.map = new google.maps.Map(el, { center, zoom: this.hasPin() ? 16 : 11, disableDefaultUI: true, zoomControl: true, gestureHandling: 'greedy' });
                        this.marker = new google.maps.Marker({ map: this.map, position: center, draggable: true });
                        this.marker.addListener('dragend', (e) => this.reverseGeocode(e.latLng));
                        this.map.addListener('click', (e) => { this.marker.setPosition(e.latLng); this.reverseGeocode(e.latLng); });
                        this.autocomplete = new google.maps.places.Autocomplete(this.$refs.placesSearch, {
                            fields: ['address_components', 'geometry'],
                            componentRestrictions: COUNTRY_CODE ? { country: COUNTRY_CODE } : undefined,
                        });
                        this.autocomplete.addListener('place_changed', () => {
                            const place = this.autocomplete.getPlace();
                            if (!place.geometry) return;
                            const loc = place.geometry.location;
                            this.map.setCenter(loc); this.map.setZoom(16); this.marker.setPosition(loc);
                            this.setLatLng(loc); this.fillFromComponents(place.address_components);
                        });
                    },
                    centreMap() {
                        if (!this.map) return;
                        google.maps.event.trigger(this.map, 'resize');
                        const c = this.pinOrDefault();
                        this.map.setCenter(c); this.map.setZoom(this.hasPin() ? 16 : 11); this.marker.setPosition(c);
                    },
                    hasPin() { return this.form.latitude !== '' && this.form.longitude !== ''; },
                    pinOrDefault() { return this.hasPin() ? { lat: parseFloat(this.form.latitude), lng: parseFloat(this.form.longitude) } : MAP_CENTER; },
                    useMyLocation() {
                        if (!navigator.geolocation) return;
                        navigator.geolocation.getCurrentPosition((pos) => {
                            const loc = new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude);
                            if (this.map) { this.map.setCenter(loc); this.map.setZoom(16); this.marker.setPosition(loc); }
                            this.reverseGeocode(loc);
                        });
                    },
                    setLatLng(loc) {
                        this.form.latitude = (typeof loc.lat === 'function' ? loc.lat() : loc.lat);
                        this.form.longitude = (typeof loc.lng === 'function' ? loc.lng() : loc.lng);
                    },
                    reverseGeocode(loc) {
                        this.setLatLng(loc);
                        this.geocoder.geocode({ location: loc }, (results, status) => {
                            if (status === 'OK' && results[0]) this.fillFromComponents(results[0].address_components);
                        });
                    },
                    fillFromComponents(components) {
                        const get = (type) => { const c = components.find((x) => x.types.includes(type)); return c ? c.long_name : ''; };
                        const line1 = [get('street_number'), get('route')].filter(Boolean).join(' ') || get('sublocality') || get('neighborhood');
                        if (line1) this.form.line1 = line1;
                        const city = get('locality') || get('postal_town') || get('administrative_area_level_2');
                        if (city) this.form.city = city;
                        const state = get('administrative_area_level_1'); if (state) { this.form.state = state; this.showMore = true; }
                        const zip = get('postal_code'); if (zip) { this.form.zip = zip; this.showMore = true; }
                        const country = get('country'); if (country) this.form.country = country;
                    },
                };
            }
        </script>
    @endpush
@endsection
