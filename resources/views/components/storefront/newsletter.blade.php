<section id="newsletter" class="bg-primary-container py-12 scroll-mt-28">
    <div class="app-container flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-4xl">send</span>
            <div>
                <h3 class="text-headline-md font-bold">Sign up to Newsletter</h3>
                <p class="text-body-base">...and receive a coupon for your first order</p>
            </div>
        </div>
        <div class="w-full md:w-1/2">
            @if (session('newsletter_status'))
                <div class="mb-3 flex items-center gap-2 bg-white/70 text-on-primary-container px-4 py-2.5 rounded-full text-label-sm font-medium">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    {{ session('newsletter_status') }}
                </div>
            @endif
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex bg-white rounded-full overflow-hidden shadow-sm">
                @csrf
                <input name="email" type="email" required placeholder="Enter your email address" value="{{ old('email') }}"
                    aria-label="Email address" class="flex-1 px-8 py-3 border-none outline-none text-body-base min-w-0">
                <button type="submit"
                    class="bg-inverse-surface text-inverse-on-surface px-8 font-bold hover:opacity-90 transition-all">
                    Sign Up
                </button>
            </form>
            @error('email', 'newsletter')
                <p class="mt-2 text-label-sm text-error flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}
                </p>
            @enderror
        </div>
    </div>
</section>
