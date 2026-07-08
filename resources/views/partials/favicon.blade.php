{{-- Favicon: the admin-configured icon (Settings → General) if set, else the
     bundled default. Shared by the storefront and admin layouts. --}}
@php($favicon = favicon_url())
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
@endif
