@extends('layouts.storefront')

@section('title', 'Contact Us — ' . config('app.name'))
@section('meta_description', 'Get in touch with ' . setting('general', 'app_name', config('app.name')) . ' — call, email, WhatsApp or send us a message.')

@php
    $address = setting('store', 'address');
    $phone = setting('store', 'phone');
    $whatsapp = setting('store', 'whatsapp');
    $email = setting('store', 'support_email') ?: setting('mail', 'from_address');
    $hours = setting('store', 'business_hours');
@endphp

@section('content')
    <section class="bg-surface-container-low border-b border-outline-variant/40">
        <div class="app-container py-12 lg:py-16 text-center max-w-2xl">
            <h1 class="text-headline-lg font-bold mb-3">We’d love to hear from you</h1>
            <p class="text-body-base text-on-surface-variant">Questions about a product, an order, or a bulk enquiry? Reach us however suits you best.</p>
        </div>
    </section>

    <div class="app-container py-12 lg:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12 items-start">

            {{-- Contact details --}}
            <div class="lg:col-span-2 space-y-4">
                @if ($address)
                    <div class="flex gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5">
                        <span class="material-symbols-outlined text-primary shrink-0">location_on</span>
                        <div>
                            <div class="font-semibold mb-0.5">Visit us</div>
                            <p class="text-label-sm text-on-surface-variant whitespace-pre-line">{{ $address }}</p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($address) }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1 text-label-sm font-semibold text-primary hover:underline mt-2">
                                Get directions <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                            </a>
                        </div>
                    </div>
                @endif

                @if ($phone)
                    <a href="tel:{{ preg_replace('/[^+0-9]/', '', $phone) }}" class="flex gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5 hover:border-primary/50 transition-colors">
                        <span class="material-symbols-outlined text-primary shrink-0">call</span>
                        <div>
                            <div class="font-semibold mb-0.5">Call us</div>
                            <p class="text-label-sm text-on-surface-variant">{{ $phone }}</p>
                        </div>
                    </a>
                @endif

                @if ($whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener" class="flex gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5 hover:border-primary/50 transition-colors">
                        <span class="material-symbols-outlined text-primary shrink-0">chat</span>
                        <div>
                            <div class="font-semibold mb-0.5">WhatsApp</div>
                            <p class="text-label-sm text-on-surface-variant">Chat with us instantly</p>
                        </div>
                    </a>
                @endif

                @if ($email)
                    <a href="mailto:{{ $email }}" class="flex gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5 hover:border-primary/50 transition-colors">
                        <span class="material-symbols-outlined text-primary shrink-0">mail</span>
                        <div>
                            <div class="font-semibold mb-0.5">Email us</div>
                            <p class="text-label-sm text-on-surface-variant break-all">{{ $email }}</p>
                        </div>
                    </a>
                @endif

                @if ($hours)
                    <div class="flex gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-5">
                        <span class="material-symbols-outlined text-primary shrink-0">schedule</span>
                        <div>
                            <div class="font-semibold mb-0.5">Business hours</div>
                            <p class="text-label-sm text-on-surface-variant">{{ $hours }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Contact form --}}
            <div class="lg:col-span-3">
                @if (session('contact_status'))
                    <div class="mb-6 flex items-start gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg text-label-sm">
                        <span class="material-symbols-outlined text-[18px] shrink-0">check_circle</span>
                        <span>{{ session('contact_status') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" x-data="contactForm()"
                    class="bg-surface-container-lowest p-6 lg:p-8 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-product-title font-semibold text-on-surface-variant">Name <span class="text-error">*</span></label>
                            <input id="name" name="name" type="text" required maxlength="150" value="{{ old('name', auth()->user()->name ?? '') }}"
                                class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('name') border-error @else border-outline-variant @enderror">
                            @error('name')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="email" class="block text-product-title font-semibold text-on-surface-variant">Email <span class="text-error">*</span></label>
                            <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', auth()->user()->email ?? '') }}"
                                class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('email') border-error @else border-outline-variant @enderror">
                            @error('email')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-product-title font-semibold text-on-surface-variant">Phone</label>
                            <div class="flex h-12 rounded border overflow-hidden bg-surface focus-within:ring-1 focus-within:ring-primary focus-within:border-primary transition-all @error('phone') border-error @else border-outline-variant @enderror">
                                <span class="grid place-items-center px-3 bg-surface-container-low text-on-surface-variant font-semibold border-r border-outline-variant select-none">03</span>
                                <input type="tel" inputmode="numeric" x-model="phoneRest" @input="phoneRest = fmtPhone(phoneRest)" maxlength="10"
                                    placeholder="00-0000000" autocomplete="tel-national" aria-label="Phone number"
                                    class="flex-1 min-w-0 px-4 outline-none bg-transparent text-body-base">
                            </div>
                            <input type="hidden" name="phone" :value="phoneFull">
                            @error('phone')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="subject" class="block text-product-title font-semibold text-on-surface-variant">Subject <span class="text-error">*</span></label>
                            <input id="subject" name="subject" type="text" required maxlength="200" value="{{ old('subject') }}"
                                class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('subject') border-error @else border-outline-variant @enderror">
                            @error('subject')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="message" class="block text-product-title font-semibold text-on-surface-variant">Message <span class="text-error">*</span></label>
                            <span class="text-[11px] text-outline"><span x-text="messageLen">0</span>/2000</span>
                        </div>
                        <textarea id="message" name="message" required rows="6" maxlength="2000"
                            @input="messageLen = $event.target.value.length"
                            placeholder="How can we help?"
                            class="w-full px-4 py-3 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('message') border-error @else border-outline-variant @enderror">{{ old('message') }}</textarea>
                        @error('message')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit"
                        class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:brightness-105 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">send</span> Send message
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Map: keyless embed works out of the box; upgrades to the official Embed API if a Maps key is set. --}}
    @if ($address)
        @php
            $mapsKey = setting('maps', 'google_maps_key');
            $mapSrc = $mapsKey
                ? 'https://www.google.com/maps/embed/v1/place?key=' . urlencode($mapsKey) . '&q=' . urlencode($address)
                : 'https://maps.google.com/maps?q=' . urlencode($address) . '&output=embed';
        @endphp
        <section class="app-container pb-12 lg:pb-16">
            <div class="rounded-xl overflow-hidden border border-outline-variant/40 bg-surface-container aspect-[16/7]">
                <iframe src="{{ $mapSrc }}" width="100%" height="100%" style="border:0;" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade" title="Our location on the map" allowfullscreen></iframe>
            </div>
        </section>
    @endif

    @push('scripts')
        <script>
            function contactForm() {
                return {
                    phoneRest: '',
                    messageLen: {{ mb_strlen((string) old('message', '')) }},
                    init() {
                        @if (old('phone')) this.seedPhone(@js(old('phone'))); @endif
                    },
                    // Same 03-prefix phone handling as the checkout / quote pages.
                    fmtPhone(v) { const d = (v || '').replace(/\D/g, '').slice(0, 9); return d.length > 2 ? d.slice(0, 2) + '-' + d.slice(2) : d; },
                    fullPhone(rest) { const d = (rest || '').replace(/\D/g, ''); return d ? (d.length > 2 ? '03' + d.slice(0, 2) + '-' + d.slice(2) : '03' + d) : ''; },
                    seedPhone(full) { const d = (full || '').replace(/\D/g, ''); const r = d.startsWith('03') ? d.slice(2) : d; this.phoneRest = this.fmtPhone(r); },
                    get phoneFull() { return this.fullPhone(this.phoneRest); },
                };
            }
        </script>
    @endpush
@endsection
