<section class="bg-primary-container py-12">
    <div class="app-container flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-4xl">send</span>
            <div>
                <h3 class="text-headline-md font-bold">Sign up to Newsletter</h3>
                <p class="text-body-base">...and receive a coupon for your first order</p>
            </div>
        </div>
        <form action="#" method="POST" class="w-full md:w-1/2 flex bg-white rounded-full overflow-hidden shadow-sm">
            @csrf
            <input name="email" type="email" required placeholder="Enter your email address"
                aria-label="Email address" class="flex-1 px-8 py-3 border-none outline-none text-body-base">
            <button type="submit"
                class="bg-inverse-surface text-inverse-on-surface px-8 font-bold hover:opacity-90 transition-all">
                Sign Up
            </button>
        </form>
    </div>
</section>
