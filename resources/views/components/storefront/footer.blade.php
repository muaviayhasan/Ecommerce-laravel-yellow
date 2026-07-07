@php
    // Find It Fast — real category departments (children of the active roots).
    $footerCategories = \App\Models\Category::query()
        ->where('is_active', true)
        ->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
        ->orderBy('sort_order')
        ->get()
        ->flatMap
        ->children
        ->take(6);

    // Contact details come from Admin → Settings → Store (with sensible fallbacks).
    $storePhone = setting('store', 'phone') ?: '(042) 111 222 333';
    $storeAddress = setting('store', 'address') ?: 'Lahore, Punjab, Pakistan';
    $storeEmail = setting('store', 'support_email');
    $storeHours = setting('store', 'business_hours');
    $telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $storePhone);

    // Information / Customer Care — every link resolves to a real route.
    $information = [
        ['label' => 'Shop All Products', 'url' => route('shop')],
        ['label' => 'Blog', 'url' => route('blog')],
        ['label' => 'Track Your Order', 'url' => route('track.order')],
        ['label' => 'Contact Us', 'url' => route('contact')],
        ['label' => 'Request a Quote', 'url' => route('quote.request')],
    ];
    $customerCare = [
        ['label' => 'My Account', 'url' => route('account')],
        ['label' => 'Wishlist', 'url' => route('wishlist')],
        ['label' => 'Compare', 'url' => route('compare')],
        ['label' => 'Shopping Cart', 'url' => route('cart')],
        ['label' => 'Customer Service', 'url' => $storeEmail ? 'mailto:' . $storeEmail : route('contact')],
    ];

    // Social / contact icons — only rendered when a destination exists.
    $socials = array_values(array_filter([
        ['icon' => 'public', 'label' => 'Facebook', 'url' => setting('seo', 'facebook_url')],
        ['icon' => 'photo_camera', 'label' => 'Instagram', 'url' => setting('seo', 'instagram_url')],
        ['icon' => 'mail', 'label' => 'Email us', 'url' => $storeEmail ? 'mailto:' . $storeEmail : null],
        ['icon' => 'call', 'label' => 'Call us', 'url' => $telHref],
    ], fn ($s) => ! empty($s['url'])));

    $paymentMethods = ['Cash on Delivery', 'Bank Transfer', 'JazzCash', 'Easypaisa', 'VISA'];
@endphp

<footer class="bg-white border-t border-outline-variant">
    <div class="app-container py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        {{-- Brand / contact --}}
        <div>
            <a class="text-headline-lg font-bold text-on-surface mb-6 block" href="{{ route('home') }}">
                {{ config('app.name') }}<span class="text-primary-container">.</span>
            </a>
            <a href="{{ $telHref }}" class="flex items-center gap-4 mb-6 group">
                <span class="material-symbols-outlined text-primary-container text-5xl">headset_mic</span>
                <div>
                    <p class="text-label-sm text-on-surface-variant">Got Questions? Call us 24/7!</p>
                    <p class="text-headline-md font-bold text-on-surface group-hover:text-primary transition-colors">{{ $storePhone }}</p>
                </div>
            </a>
            <div class="space-y-1">
                <p class="text-body-base font-bold mb-1">Contact Info</p>
                <p class="text-label-sm text-on-surface-variant">{{ $storeAddress }}</p>
                @if ($storeEmail)
                    <p class="text-label-sm text-on-surface-variant">
                        <a href="mailto:{{ $storeEmail }}" class="hover:text-primary transition-colors">{{ $storeEmail }}</a>
                    </p>
                @endif
                @if ($storeHours)
                    <p class="text-label-sm text-on-surface-variant">{{ $storeHours }}</p>
                @endif
            </div>
            @if ($socials)
                <div class="flex gap-4 mt-6">
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}"
                            @if (\Illuminate\Support\Str::startsWith($social['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary-container transition-colors">
                            <span class="material-symbols-outlined text-xl">{{ $social['icon'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Find It Fast — real categories --}}
        <div>
            <h4 class="font-bold text-headline-md mb-6">Find It Fast</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                @forelse ($footerCategories as $category)
                    <li>
                        <a href="{{ route('shop', ['category' => $category->slug]) }}"
                            class="hover:text-primary transition-colors">{{ $category->name }}</a>
                    </li>
                @empty
                    <li><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">Shop all products</a></li>
                @endforelse
            </ul>
        </div>

        {{-- Information --}}
        <div>
            <h4 class="font-bold text-headline-md mb-6">Information</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                @foreach ($information as $link)
                    <li><a href="{{ $link['url'] }}" class="hover:text-primary transition-colors">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Customer Care --}}
        <div>
            <h4 class="font-bold text-headline-md mb-6">Customer Care</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                <li><a href="{{ route('quote.request') }}" class="hover:text-primary transition-colors font-medium">Request a Quote</a></li>
                @foreach ($customerCare as $link)
                    <li><a href="{{ $link['url'] }}" class="hover:text-primary transition-colors">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Extra bottom padding on mobile so the copyright bar clears the fixed bottom nav. --}}
    <div class="border-t border-outline-variant bg-surface-container-high py-6 pb-24 md:pb-6">
        <div class="app-container flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-label-sm text-on-surface-variant">
                &copy; {{ date('Y') }} {{ config('app.name') }} — All Rights Reserved
            </p>
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-on-surface-variant font-bold opacity-60">
                @foreach ($paymentMethods as $pay)
                    <span class="text-sm">{{ $pay }}</span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
