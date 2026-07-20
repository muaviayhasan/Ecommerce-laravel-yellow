@php
    use Illuminate\Support\Str;

    $siteName = config('app.name');

    // Inline @section values arrive HTML-escaped (Blade e()s them), while the
    // setting()/url() fallbacks arrive raw. Decode once so every value is plain
    // text, then the single {{ }} escape below applies uniformly — otherwise
    // titles containing "&" render as "&amp;" in the tab and OG tags.
    $plain = fn (string $v): string => html_entity_decode(trim($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $title = $plain($__env->yieldContent('title', $siteName));
    $desc = $plain($__env->yieldContent('meta_description', (string) setting('seo', 'meta_description', '')));
    $canonical = $plain($__env->yieldContent('canonical', url()->current()));

    $ogType = trim($__env->yieldContent('og_type', 'website'));
    $ogImage = $plain($__env->yieldContent('og_image', (string) setting('seo', 'og_image', '')));
    if ($ogImage !== '' && ! Str::startsWith($ogImage, ['http://', 'https://'])) {
        $ogImage = url($ogImage);
    }

    // A site-wide "indexable" off (e.g. staging) overrides any per-page value.
    $indexable = (bool) setting('seo', 'indexable', true);
    $robots = $indexable ? trim($__env->yieldContent('robots', 'index, follow')) : 'noindex, nofollow';

    $keywords = (string) setting('seo', 'meta_keywords', '');
    $twitter = (string) setting('seo', 'twitter_handle', '');
    $verify = (string) setting('seo', 'google_site_verification', '');

    $sameAs = array_values(array_filter([setting('seo', 'facebook_url'), setting('seo', 'instagram_url')]));

    $graph = [
        array_filter([
            '@type' => 'Organization',
            '@id' => url('/') . '#organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $ogImage ?: null,
            'sameAs' => $sameAs ?: null,
        ]),
        [
            '@type' => 'WebSite',
            '@id' => url('/') . '#website',
            'name' => $siteName,
            'url' => url('/'),
            'publisher' => ['@id' => url('/') . '#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('shop') . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
@endphp
<title>{{ $title }}</title>
@if ($desc)<meta name="description" content="{{ $desc }}">@endif
@if ($keywords)<meta name="keywords" content="{{ $keywords }}">@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($verify)<meta name="google-site-verification" content="{{ $verify }}">@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
@if ($desc)<meta property="og:description" content="{{ $desc }}">@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
@if ($twitter)<meta name="twitter:site" content="{{ $twitter }}">@endif
<meta name="twitter:title" content="{{ $title }}">
@if ($desc)<meta name="twitter:description" content="{{ $desc }}">@endif
@if ($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

{{-- Global structured data --}}
<script type="application/ld+json">@json(['@context' => 'https://schema.org', '@graph' => $graph])</script>
