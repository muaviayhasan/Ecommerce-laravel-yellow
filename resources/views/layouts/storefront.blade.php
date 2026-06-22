<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO: a page sets @section('title') / @push('meta'); falls back to the store name. --}}
    <title>@yield('title', config('app.name'))</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @stack('meta')

    {{-- Fonts: Work Sans (body) + Material Symbols (icons), matching the storefront theme. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&display=swap">
    {{-- Material Symbols is imported via app.css (in a cascade layer) so icon size
         utilities work; see resources/css/app.css. --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>

<body class="min-h-screen bg-background text-on-surface antialiased">
    <x-storefront.header />

    <main>
        @yield('content')
    </main>

    <x-storefront.newsletter />
    <x-storefront.footer />

    @livewireScripts
    @stack('scripts')
</body>

</html>
