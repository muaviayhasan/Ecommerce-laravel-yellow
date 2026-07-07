@extends('layouts.storefront')

@section('title', 'About Us — ' . config('app.name'))
@section('meta_description', 'Learn about ' . setting('general', 'app_name', config('app.name')) . ' — who we are, what we stand for, and why customers trust us.')

@php
    $store = setting('general', 'app_name', config('app.name'));
    $values = [
        ['icon' => 'verified', 'title' => 'Genuine products', 'text' => 'Every item is sourced from trusted brands and backed by a real manufacturer warranty.'],
        ['icon' => 'local_shipping', 'title' => 'Fast, careful delivery', 'text' => 'Orders are packed with care and dispatched quickly, with tracking every step of the way.'],
        ['icon' => 'payments', 'title' => 'Fair, transparent pricing', 'text' => 'Honest prices with no hidden charges — and clear quotes for bulk or custom orders.'],
        ['icon' => 'support_agent', 'title' => 'People who help', 'text' => 'A friendly support team you can reach by chat, phone or email — before and after you buy.'],
    ];
@endphp

@section('content')
    {{-- Hero --}}
    <section class="bg-surface-container-low border-b border-outline-variant/40">
        <div class="app-container py-16 lg:py-24 text-center max-w-3xl">
            <span class="inline-flex items-center gap-2 text-label-sm font-semibold text-primary bg-primary-container/30 px-3 py-1 rounded-full mb-5">
                <span class="material-symbols-outlined text-[16px]">storefront</span> About {{ $store }}
            </span>
            <h1 class="text-headline-lg lg:text-5xl font-bold mb-4 leading-tight">Quality you can rely on, service you can trust</h1>
            <p class="text-lg text-on-surface-variant">
                {{ $store }} is your one-stop shop for dependable products at honest prices. We bring together
                trusted brands, careful service and genuine support so shopping with us always feels easy.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
                <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 bg-primary-container text-on-surface font-bold px-7 py-3 rounded-full hover:brightness-105 active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[20px]">shopping_bag</span> Start shopping
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 border border-outline text-on-surface font-semibold px-7 py-3 rounded-full hover:bg-surface-container transition-all">
                    Get in touch
                </a>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    @if ($stats['products'] > 0)
        <section class="app-container py-10">
            <div class="grid grid-cols-3 gap-4 max-w-3xl mx-auto text-center">
                <div class="p-4">
                    <div class="text-headline-md lg:text-4xl font-bold text-primary">{{ number_format($stats['products']) }}</div>
                    <div class="text-label-sm text-on-surface-variant mt-1">Products in stock</div>
                </div>
                <div class="p-4 border-x border-outline-variant/40">
                    <div class="text-headline-md lg:text-4xl font-bold text-primary">{{ number_format($stats['brands']) }}</div>
                    <div class="text-label-sm text-on-surface-variant mt-1">Trusted brands</div>
                </div>
                <div class="p-4">
                    <div class="text-headline-md lg:text-4xl font-bold text-primary">{{ number_format($stats['orders']) }}+</div>
                    <div class="text-label-sm text-on-surface-variant mt-1">Orders delivered</div>
                </div>
            </div>
        </section>
    @endif

    {{-- Story --}}
    <section class="app-container py-12 lg:py-16">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-headline-md font-bold mb-4">Our story</h2>
            <div class="space-y-4 text-body-base text-on-surface-variant leading-relaxed">
                <p>
                    {{ $store }} started with a simple idea: buying the things you need for your home and work
                    should be straightforward, fairly priced and genuinely well supported. No pushy sales, no
                    surprises at checkout — just good products and honest help.
                </p>
                <p>
                    Today we carry a growing range from brands we believe in, and we stand behind everything we
                    sell. Whether you’re buying a single item or need a custom quote for a larger order, our team
                    is here to make it effortless.
                </p>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="bg-surface-container-low border-y border-outline-variant/40">
        <div class="app-container py-14 lg:py-20">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-headline-md font-bold mb-2">Why shop with us</h2>
                <p class="text-body-base text-on-surface-variant">The promises behind every order.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($values as $value)
                    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-6 text-center">
                        <div class="w-12 h-12 mx-auto grid place-items-center rounded-full bg-primary-container/40 text-primary mb-4">
                            <span class="material-symbols-outlined">{{ $value['icon'] }}</span>
                        </div>
                        <h3 class="font-bold mb-1.5">{{ $value['title'] }}</h3>
                        <p class="text-label-sm text-on-surface-variant leading-relaxed">{{ $value['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="app-container py-14 lg:py-20">
        <div class="max-w-4xl mx-auto bg-primary-container/25 border border-primary-container/50 rounded-2xl p-8 lg:p-12 text-center">
            <h2 class="text-headline-md font-bold mb-3">Ready when you are</h2>
            <p class="text-body-base text-on-surface-variant max-w-xl mx-auto mb-7">
                Browse the shop, track an order, or ask us anything. We’d love to help you find exactly what you need.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 bg-primary-container text-on-surface font-bold px-7 py-3 rounded-full hover:brightness-105 active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[20px]">shopping_bag</span> Shop now
                </a>
                <a href="{{ route('quote.request') }}" class="inline-flex items-center gap-2 border border-outline text-on-surface font-semibold px-7 py-3 rounded-full hover:bg-surface-container transition-all">
                    <span class="material-symbols-outlined text-[20px]">request_quote</span> Request a quote
                </a>
            </div>
        </div>
    </section>
@endsection
