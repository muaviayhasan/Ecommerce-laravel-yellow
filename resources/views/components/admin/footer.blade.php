<footer class="pt-8 mt-auto border-t border-outline-variant/40 pb-2">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-outline font-medium">
        <p>Copyright © {{ now()->year }} {{ setting('general', 'app_name', config('app.name')) }}. All rights reserved.</p>
        <div class="flex gap-6">
            <a href="#" class="hover:text-primary transition-colors">Store Locator</a>
            <a href="#" class="hover:text-primary transition-colors">Track Order</a>
            <a href="#" class="hover:text-primary transition-colors">Privacy Policy</a>
        </div>
    </div>
</footer>
