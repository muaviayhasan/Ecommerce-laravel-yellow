@php
    $findItFast = ['Laptops & Computers', 'Cameras & Photography', 'Smart Phones & Tablets', 'Video Games & Consoles', 'TV & Audio', 'Gadgets'];
    $information = ['About', 'Contact', 'Wishlist', 'Compare', 'FAQ', 'Store Directory'];
    $customerCare = ['My Account', 'Track your Order', 'Customer Service', 'Returns/Exchange', 'FAQs', 'Product Support'];
@endphp

<footer class="bg-white border-t border-outline-variant">
    <div class="app-container py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        {{-- Brand / contact --}}
        <div>
            <a class="text-headline-lg font-bold text-on-surface mb-6 block" href="{{ route('home') }}">
                {{ config('app.name') }}<span class="text-primary-container">.</span>
            </a>
            <div class="flex items-center gap-4 mb-6">
                <span class="material-symbols-outlined text-primary-container text-5xl">headset_mic</span>
                <div>
                    <p class="text-label-sm text-on-surface-variant">Got Questions? Call us 24/7!</p>
                    <p class="text-headline-md font-bold text-on-surface">(800) 8001-8588</p>
                </div>
            </div>
            <div>
                <p class="text-body-base font-bold mb-2">Contact Info</p>
                <p class="text-label-sm text-on-surface-variant">Lahore, Punjab, Pakistan</p>
            </div>
            <div class="flex gap-4 mt-6">
                @foreach (['public', 'photo_camera', 'rss_feed', 'movie'] as $icon)
                    <a href="#" aria-label="Social link"
                        class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center hover:bg-primary-container transition-colors">
                        <span class="material-symbols-outlined text-xl">{{ $icon }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="font-bold text-headline-md mb-6">Find It Fast</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                @foreach ($findItFast as $link)
                    <li><a href="{{ route('shop') }}" class="hover:text-primary transition-colors">{{ $link }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h4 class="font-bold text-headline-md mb-6">Information</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                @foreach ($information as $link)
                    <li><a href="#" class="hover:text-primary transition-colors">{{ $link }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h4 class="font-bold text-headline-md mb-6">Customer Care</h4>
            <ul class="space-y-3 text-body-base text-on-surface-variant">
                <li><a href="{{ route('quote.request') }}" class="hover:text-primary transition-colors font-medium">Request a Quote</a></li>
                @foreach ($customerCare as $link)
                    <li><a href="#" class="hover:text-primary transition-colors">{{ $link }}</a></li>
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
            <div class="flex gap-4 text-on-surface-variant font-bold opacity-50">
                @foreach (['DISCOVER', 'MasterCard', 'PayPal', 'VISA'] as $pay)
                    <span class="text-sm">{{ $pay }}</span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
