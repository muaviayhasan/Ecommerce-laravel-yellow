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

            <form method="POST" action="{{ route('quote.store') }}"
                class="bg-surface-container-lowest p-6 lg:p-8 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label for="name" class="block text-product-title font-semibold text-on-surface-variant">Name <span class="text-error">*</span></label>
                        <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', auth()->user()->name ?? '') }}"
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
                        <label for="phone" class="block text-product-title font-semibold text-on-surface-variant">Phone</label>
                        <input id="phone" name="phone" type="text" maxlength="30" value="{{ old('phone') }}" data-mask="phone"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base border-outline-variant">
                    </div>
                    <div class="space-y-1.5">
                        <label for="company" class="block text-product-title font-semibold text-on-surface-variant">Company</label>
                        <input id="company" name="company" type="text" maxlength="255" value="{{ old('company') }}"
                            class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base border-outline-variant">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="message" class="block text-product-title font-semibold text-on-surface-variant">What do you need? <span class="text-error">*</span></label>
                    <textarea id="message" name="message" required rows="5" maxlength="2000"
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
@endsection
