@extends('layouts.storefront')

@section('title', 'Request a quote — ' . config('app.name'))
@section('meta_description', 'Tell us what you need and we’ll prepare a custom quotation for you.')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh]">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-headline-md font-bold mb-2">Request a quote</h1>
                <p class="text-body-base text-on-surface-variant">Tell us what you’re looking for — quantities, products or a custom requirement — and our team will get back to you with pricing.</p>
            </div>

            @if (session('quote_status'))
                <div class="mb-6 flex items-start gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg text-label-sm">
                    <span class="material-symbols-outlined text-[18px] shrink-0">check_circle</span>
                    <span>{{ session('quote_status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('quote.store') }}" x-data="quoteForm()"
                class="bg-surface-container-lowest p-6 lg:p-8 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="name" class="block text-product-title font-semibold text-on-surface-variant">Name <span class="text-error">*</span></label>
                            <span class="text-[11px] text-outline"><span x-text="nameLen">0</span>/150</span>
                        </div>
                        <input id="name" name="name" type="text" required maxlength="150" value="{{ old('name', auth()->user()->name ?? '') }}"
                            @input="nameLen = $event.target.value.length"
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
                        <div class="flex items-center justify-between">
                            <label for="company" class="block text-product-title font-semibold text-on-surface-variant">Company</label>
                            <span class="text-[11px] text-outline"><span x-text="companyLen">0</span>/150</span>
                        </div>
                        <input id="company" name="company" type="text" maxlength="150" value="{{ old('company') }}"
                            @input="companyLen = $event.target.value.length"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base border-outline-variant">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="message" class="block text-product-title font-semibold text-on-surface-variant">What do you need? <span class="text-error">*</span></label>
                        <span class="text-[11px] text-outline"><span x-text="messageLen">0</span>/2000</span>
                    </div>
                    <textarea id="message" name="message" required rows="5" maxlength="2000"
                        @input="messageLen = $event.target.value.length"
                        placeholder="List the products and quantities, or describe your requirement."
                        class="w-full px-4 py-3 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('message') border-error @else border-outline-variant @enderror">{{ old('message', $product ? "I'd like a quote for: {$product}" : '') }}</textarea>
                    @error('message')<p class="text-error text-label-sm">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">request_quote</span> Send request
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function quoteForm() {
                return {
                    phoneRest: '',
                    nameLen: {{ mb_strlen((string) old('name', auth()->user()->name ?? '')) }},
                    companyLen: {{ mb_strlen((string) old('company', '')) }},
                    messageLen: {{ mb_strlen((string) old('message', $product ? "I'd like a quote for: {$product}" : '')) }},
                    init() {
                        @if (old('phone')) this.seedPhone(@js(old('phone'))); @endif
                    },
                    // Same 03-prefix phone handling as the checkout page.
                    fmtPhone(v) { const d = (v || '').replace(/\D/g, '').slice(0, 9); return d.length > 2 ? d.slice(0, 2) + '-' + d.slice(2) : d; },
                    fullPhone(rest) { const d = (rest || '').replace(/\D/g, ''); return d ? (d.length > 2 ? '03' + d.slice(0, 2) + '-' + d.slice(2) : '03' + d) : ''; },
                    seedPhone(full) { const d = (full || '').replace(/\D/g, ''); const r = d.startsWith('03') ? d.slice(2) : d; this.phoneRest = this.fmtPhone(r); },
                    get phoneFull() { return this.fullPhone(this.phoneRest); },
                };
            }
        </script>
    @endpush
@endsection
