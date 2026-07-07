<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO: title, description, canonical, robots, Open Graph, Twitter + global JSON-LD.
         Pages override via @section('title'|'meta_description'|'canonical'|'robots'|'og_image'|'og_type')
         and add page-specific structured data with @push('schema'). --}}
    @include('storefront.partials.seo')
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
    @stack('schema')
    @stack('head')
</head>

<body class="min-h-screen bg-background text-on-surface antialiased">
    <x-storefront.header />

    <main>
        @yield('content')
    </main>

    {{-- Pages can hide the newsletter with @section('hideNewsletter', '1') (e.g. auth pages). --}}
    @sectionMissing('hideNewsletter')
        <x-storefront.newsletter />
    @endif
    <x-storefront.footer />

    {{-- Compare shortlist: floating bar + store shared with the chat widget (so it lifts clear). --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('compareBar', { visible: {{ app(\App\Services\CompareService::class)->count() > 0 ? 'true' : 'false' }} });
        });
    </script>
    <x-storefront.compare-tray />

    <x-storefront.support-chat />

    <x-storefront.mobile-nav />

    {{-- Keep your place: cart / wishlist / compare actions reload the page, so we save the
         scroll position on submit and restore it after the reload — you land back on the
         same item instead of at the top of the page. --}}
    <script>
        (function () {
            var KEY = 'sf:keepScroll';
            // Add-to-cart, wishlist and compare toggles/removes all POST and redirect back here.
            var WATCH = /\/(cart|wishlist|compare)(\/|$)/;
            function here() { return location.pathname + location.search; }

            // Record where we are the moment one of those forms is submitted.
            document.addEventListener('submit', function (e) {
                var f = e.target;
                if (!(f instanceof HTMLFormElement) || f.hasAttribute('data-no-keep-scroll')) return;
                if (!WATCH.test(f.getAttribute('action') || '')) return;
                try { sessionStorage.setItem(KEY, JSON.stringify({ u: here(), y: window.scrollY, t: Date.now() })); } catch (err) {}
            }, true);

            // After the reload, if we're back on the same page, jump to where we were.
            function restore() {
                var s;
                try { s = JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (err) { s = null; }
                if (!s || s.u !== here() || (Date.now() - s.t) > 10000) {
                    try { sessionStorage.removeItem(KEY); } catch (err) {}
                    return;
                }
                try { sessionStorage.removeItem(KEY); } catch (err) {}
                var y = s.y || 0;
                window.scrollTo(0, y);
                // Re-apply once late layout shifts (images, fonts) settle.
                requestAnimationFrame(function () { window.scrollTo(0, y); });
                window.addEventListener('load', function () { window.scrollTo(0, y); }, { once: true });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restore);
            else restore();
        })();
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
