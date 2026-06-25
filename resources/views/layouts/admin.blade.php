<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-scope">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') · {{ setting('general', 'app_name', config('app.name')) }}</title>

    {{-- Apply the saved theme before paint to avoid a flash of the wrong mode. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('admin-theme');
                var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    {{-- Inter (admin body font) + Material Symbols (icons via app.css cascade layer). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>

<body class="bg-background text-on-background min-h-screen flex antialiased">
    <x-admin.sidebar />

    {{-- Main column --}}
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto">
        <x-admin.header />

        <div class="p-6 lg:p-8 space-y-6 flex-1">
            <x-admin.flash />
            @yield('content')

            <x-admin.footer />
        </div>
    </main>

    @livewireScripts
    @stack('scripts')
</body>

</html>
