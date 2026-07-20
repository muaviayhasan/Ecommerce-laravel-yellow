<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-scope">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') · {{ setting('general', 'app_name', config('app.name')) }}</title>

    @include('partials.favicon')

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

{{-- h-screen + overflow-hidden: the document itself never scrolls — all admin
     scrolling happens inside <main>. Body-level helpers appended by libraries
     (Select2 dropdowns, TinyMCE's sink) can otherwise extend the page below the
     viewport and cause a second, document-level scrollbar. --}}
<body class="bg-background text-on-background h-screen overflow-hidden flex antialiased">
    <x-admin.sidebar />

    {{-- Main column --}}
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto">
        <x-admin.header />

        <div class="p-6 lg:p-8 flex flex-col gap-6 flex-1">
            <x-admin.flash />
            @yield('content')

            <x-admin.footer />
        </div>
    </main>

    @livewireScripts
    @stack('scripts')

    {{-- Reusable client-side table sort: <table x-data="sortableTable"> with
         <tr data-sortable data-<col>="…"> rows and <x-admin.sort-th col="…"> headers. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sortableTable', () => ({
                sortCol: null,
                sortDir: 'asc',
                sortBy(col, type = 'text') {
                    if (this.sortCol === col) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortCol = col;
                        this.sortDir = 'asc';
                    }
                    const tbody = this.$root.querySelector('tbody'); // $root = the <table> (x-data), not the clicked button
                    if (!tbody) return;
                    const rows = Array.from(tbody.querySelectorAll('tr[data-sortable]'));
                    rows.sort((a, b) => {
                        let av = a.dataset[col] ?? '';
                        let bv = b.dataset[col] ?? '';
                        if (type === 'num') { av = parseFloat(av) || 0; bv = parseFloat(bv) || 0; }
                        else { av = String(av).toLowerCase(); bv = String(bv).toLowerCase(); }
                        const cmp = av < bv ? -1 : av > bv ? 1 : 0;
                        return this.sortDir === 'asc' ? cmp : -cmp;
                    });
                    rows.forEach((r) => tbody.appendChild(r));
                },
            }));
        });
    </script>

    {{-- Keep the session + CSRF token fresh so long-open forms don't 419 on submit. --}}
    <script>
        (function () {
            var url = @json(route('admin.keep-alive'));
            setInterval(function () {
                fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) {
                        if (!d || !d.token) return;
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', d.token);
                        document.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = d.token; });
                    })
                    .catch(function () {});
            }, 240000); // every 4 min — comfortably under the session lifetime
        })();
    </script>
</body>

</html>
