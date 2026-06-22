@extends('layouts.storefront')

@section('title', $pageTitle . ' — ' . config('app.name'))

@section('content')
    <section class="app-container py-24 text-center">
        <span class="material-symbols-outlined text-7xl text-primary-container mb-4">construction</span>
        <h1 class="text-headline-lg mb-3">{{ $pageTitle }}</h1>
        <p class="text-body-base text-on-surface-variant mb-8 max-w-md mx-auto">
            This page is coming soon. It will be built as part of the next module.
        </p>
        <a href="{{ route('home') }}"
            class="inline-flex items-center gap-2 bg-primary-container px-8 py-3 rounded-full font-bold hover:opacity-90 transition-all">
            <span class="material-symbols-outlined">arrow_back</span> Back to Home
        </a>
    </section>
@endsection
