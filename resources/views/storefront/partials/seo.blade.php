@php
    use Illuminate\Support\Str;

    $siteName = config('app.name');

    // Inline @section values arrive HTML-escaped (Blade e()s them), while the
    // setting()/url() fallbacks arrive raw. Decode once so every value is plain
    // text, then the single {{ }} escape below applies uniformly — otherwise
    // titles containing "&" render as "&amp;" in the tab and OG tags.
    $plain = fn (string $v): string => html_entity_decode(trim($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $title = $plain($__env->yieldContent('title', $siteName));

    /*
     | Description, with a terminal length clamp.
     |
     | The fallback chain lives where the data does — a product page @sections its
     | own (meta_description, else a trimmed description), a shop page builds one
     | from the category, everything else falls through to the site default. That
     | is the right shape: this partial has no product or category in scope and
     | should not go looking for one.
     |
     | What was missing is a floor under all of them. The homepage was emitting 165
     | characters, which Google truncates mid-sentence. Clamping here rather than at
     | each call site makes an over-length description structurally impossible no
     | matter which page sets it, or who adds a page later. Cut back to the last
     | whole word so it never ends on a fragment.
     */
    $desc = $plain($__env->yieldContent('meta_description', (string) setting('seo', 'meta_description', '')));
    if (mb_strlen($desc) > 155) {
        $desc = Str::limit($desc, 155, '');
        $break = mb_strrpos($desc, ' ');
        $desc = rtrim($break ? mb_substr($desc, 0, $break) : $desc, " ,;:-—");
    }

    $canonical = $plain($__env->yieldContent('canonical', url()->current()));

    $ogType = trim($__env->yieldContent('og_type', 'website'));
    /*
     | Pages with a natural image of their own @section it (product shot, category
     | banner, blog cover). Everything else falls through to Settings → SEO →
     | "Default share image", and when that is blank, to the bundled storefront
     | banner — which ships with the code, so sharing renders a real image card on
     | every page out of the box instead of only after someone fills the setting.
     */
    $ogImage = $plain($__env->yieldContent('og_image', (string) setting('seo', 'og_image', '')));
    if ($ogImage === '') {
        $ogImage = asset('images/meta/og-default.png');
    }
    if ($ogImage !== '' && ! Str::startsWith($ogImage, ['http://', 'https://'])) {
        $ogImage = url($ogImage);
    }

    // A site-wide "indexable" off (e.g. staging) overrides any per-page value.
    $indexable = (bool) setting('seo', 'indexable', true);
    $robots = $indexable ? trim($__env->yieldContent('robots', 'index, follow')) : 'noindex, nofollow';

    /*
     | No $keywords here on purpose. <meta name="keywords"> has not been a Google
     | signal since 2009, and this site emitted one identical string on every page —
     | which at best says nothing and at worst reads as keyword stuffing.
     |
     | The setting itself is kept: Admin → Settings → SEO still stores it, and the
     | column is still on products. It is simply never rendered. Nothing queries it
     | (site search matches product.name only), so nothing else changes.
     */
    $twitter = (string) setting('seo', 'twitter_handle', '');
    $verify = (string) setting('seo', 'google_site_verification', '');

    $sameAs = array_values(array_filter([setting('seo', 'facebook_url'), setting('seo', 'instagram_url')]));

    /*
     | LocalBusiness. This is the node that feeds the Google map pack — the results
     | for "air cooler shop near me" and similar — which for a shop with a counter
     | is worth more than most on-page work. Emitted only when a city has been set
     | in Admin → Settings → Store → Location, because a LocalBusiness with a
     | half-built address is worse than none: Google will happily show a wrong or
     | incomplete location to someone trying to drive to you.
     |
     | HomeGoodsStore is a LocalBusiness subtype and the closest match to a shop
     | selling appliances; being specific gives Google more to work with than the
     | generic type would.
     */
    $localBusiness = null;
    if ($city = trim((string) setting('store', 'city', ''))) {
        $dayMap = [
            'mon-sat' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'mon-fri' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'mon-sun' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'sat-thu' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
        ];
        $days = $dayMap[setting('store', 'opening_days', '')] ?? null;
        $opens = trim((string) setting('store', 'opens', ''));
        $closes = trim((string) setting('store', 'closes', ''));

        $lat = trim((string) setting('store', 'latitude', ''));
        $lng = trim((string) setting('store', 'longitude', ''));

        $localBusiness = array_filter([
            '@type' => 'HomeGoodsStore',
            '@id' => url('/') . '#localbusiness',
            'name' => $siteName,
            'url' => url('/'),
            'image' => $ogImage ?: null,
            'telephone' => setting('store', 'phone') ?: null,
            'email' => setting('store', 'support_email') ?: null,
            'parentOrganization' => ['@id' => url('/') . '#organization'],
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => trim((string) setting('store', 'address', '')) ?: null,
                'addressLocality' => $city,
                'addressRegion' => setting('store', 'region') ?: null,
                'postalCode' => setting('store', 'postal_code') ?: null,
                'addressCountry' => setting('store', 'country') ?: 'PK',
            ]),
            'geo' => ($lat !== '' && $lng !== '') ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ] : null,
            'openingHoursSpecification' => ($days && $opens && $closes) ? [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => $opens,
                'closes' => $closes,
            ]] : null,
            // Derived from the live catalogue rather than asked for — it is a fact the
            // database already knows, and Google uses it to qualify local results.
            'priceRange' => ($range = \App\Support\Storefront::priceRangeLabel()) ?: null,
            'sameAs' => $sameAs ?: null,
        ]);
    }

    $graph = [
        array_filter([
            '@type' => 'Organization',
            '@id' => url('/') . '#organization',
            'name' => $siteName,
            'url' => url('/'),
            // The actual logo, not the share banner — Google shows Organization.logo
            // as the brand mark in knowledge panels.
            'logo' => ($logoPath = logo_url()) ? url($logoPath) : ($ogImage ?: null),
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

    if ($localBusiness) {
        $graph[] = $localBusiness;
    }
@endphp
<title>{{ $title }}</title>
@if ($desc)<meta name="description" content="{{ $desc }}">@endif
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
{{-- Open Graph wants language_TERRITORY. app()->getLocale() is just "en", which is
     not a valid og:locale and gets ignored — so the territory comes from config. --}}
<meta property="og:locale" content="{{ config('app.og_locale', 'en_PK') }}">

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
@if ($twitter)<meta name="twitter:site" content="{{ $twitter }}">@endif
<meta name="twitter:title" content="{{ $title }}">
@if ($desc)<meta name="twitter:description" content="{{ $desc }}">@endif
@if ($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

{{-- Global structured data --}}
<script type="application/ld+json">@json(['@context' => 'https://schema.org', '@graph' => $graph])</script>
