{{-- Google reCAPTCHA v3 (invisible) — drop inside a <form>, before the submit
     button, with a distinct action per form:

         <x-recaptcha action="contact" />

     Renders a hidden recaptcha_token input; the paired server check is
     Recaptcha::rules('contact') on the handler. Renders nothing when no keys
     are configured (local/staging).

     The ~250KB Google api.js is loaded on the visitor's FIRST INTERACTION with
     a protected form (focus/tap), never on page load — the newsletter form sits
     in the footer of every page and must not cost the homepage its LCP. On
     submit the handler fetches a fresh token (they expire after 2 minutes, so
     minting at submit time also avoids stale-token failures), fills the hidden
     field and resubmits. If Google's script fails to load, the form submits
     without a token and the server rule decides (it fails open on network
     errors). 'bag' is the error bag to read from — the newsletter form
     validates into a named bag. --}}
@props(['action', 'bag' => 'default', 'attribution' => true])

@if (\App\Rules\Recaptcha::enabled())
    <input type="hidden" name="recaptcha_token" value="" data-recaptcha-action="{{ $action }}">

    {{-- Google's floating badge is hidden (it overlaps the support-chat bubble
         and the mobile bottom nav), which their ToS permits only if this
         attribution is shown in the form flow. Newsletter passes
         :attribution="false" and renders it outside its flex-row form. --}}
    @if ($attribution)
        <p class="text-label-sm text-on-surface-variant">
            This site is protected by reCAPTCHA and the Google
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="underline">Privacy Policy</a> and
            <a href="https://policies.google.com/terms" target="_blank" rel="noopener" class="underline">Terms of Service</a> apply.
        </p>
    @endif

    {{-- isset guard: the 404 page renders the storefront layout (and its footer
         newsletter form) without the web middleware, so $errors is never shared. --}}
    @if (isset($errors) && $errors->getBag($bag)->has('recaptcha_token'))
        <p class="text-error text-label-sm flex items-center gap-1">
            <span aria-hidden="true" class="material-symbols-outlined text-[16px]">error</span>{{ $errors->getBag($bag)->first('recaptcha_token') }}
        </p>
    @endif

    @once
        @push('scripts')
            <style>
                /* Badge hidden — attribution text above replaces it (Google-permitted). */
                .grecaptcha-badge { visibility: hidden !important; }
            </style>
            <script>
                (function () {
                    var KEY = @json((string) config('services.recaptcha.site_key'));
                    var apiPromise = null;

                    function api() {
                        if (!apiPromise) {
                            apiPromise = new Promise(function (resolve, reject) {
                                var s = document.createElement('script');
                                s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(KEY);
                                s.async = true;
                                s.onload = function () { window.grecaptcha.ready(function () { resolve(window.grecaptcha); }); };
                                s.onerror = function () { reject(new Error('reCAPTCHA api.js failed to load')); };
                                document.head.appendChild(s);
                            });
                        }
                        return apiPromise;
                    }

                    // Start loading as soon as someone touches a protected form, so the
                    // API is usually ready by the time they submit.
                    function warm(e) {
                        var form = e.target && e.target.closest ? e.target.closest('form') : null;
                        if (!form || !form.querySelector('input[data-recaptcha-action]')) return;
                        api().catch(function () {});
                        document.removeEventListener('focusin', warm, true);
                        document.removeEventListener('pointerdown', warm, true);
                    }
                    document.addEventListener('focusin', warm, true);
                    document.addEventListener('pointerdown', warm, true);

                    document.addEventListener('submit', function (e) {
                        var form = e.target;
                        if (!(form instanceof HTMLFormElement)) return;
                        var field = form.querySelector('input[data-recaptcha-action]');
                        if (!field) return;

                        // Native validation has already passed by the time submit fires.
                        // form.submit() below bypasses this listener, so no loop.
                        e.preventDefault();
                        api().then(function (g) {
                            return g.execute(KEY, { action: field.getAttribute('data-recaptcha-action') });
                        }).then(function (token) {
                            field.value = token;
                            form.submit();
                        }).catch(function () {
                            // Submit without a token — the server rule fails open on
                            // network problems; better than a dead submit button.
                            form.submit();
                        });
                    }, true);
                })();
            </script>
        @endpush
    @endonce
@endif
