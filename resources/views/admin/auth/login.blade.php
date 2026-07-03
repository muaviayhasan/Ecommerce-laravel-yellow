<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-scope">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · {{ setting('general', 'app_name', config('app.name')) }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">

    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="admin-scope bg-background text-on-surface min-h-screen flex items-center justify-center p-6 relative overflow-hidden antialiased">

    {{-- Atmospheric background --}}
    <div class="absolute top-[-10%] left-[-5%] w-[40%] h-[40%] bg-surface-container rounded-full blur-[120px] opacity-60 z-0"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[40%] bg-primary-fixed-dim rounded-full blur-[120px] opacity-20 z-0"></div>

    <main class="relative z-10 w-full max-w-[440px]">
        {{-- Brand --}}
        <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-2 mb-2">
                <div class="w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-white" style="font-variation-settings:'FILL' 1;">dataset</span>
                </div>
                <span class="text-3xl font-black text-primary tracking-tight">{{ setting('general', 'app_name', config('app.name')) }}</span>
            </div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-outline">Admin Control Panel</p>
        </div>

        {{-- Login card --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm p-6 md:p-10">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-on-surface mb-1">Sign In</h1>
                <p class="text-sm text-on-surface-variant">Staff access only — enter your credentials to continue.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 flex items-start gap-2 rounded-lg bg-error/10 border border-error/25 px-4 py-3 text-sm text-error">
                    <span class="material-symbols-outlined text-[18px] mt-px shrink-0">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg bg-error/10 border border-error/25 px-4 py-3 text-sm text-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-6">
                @csrf

                {{-- Email / phone --}}
                <div class="space-y-2">
                    <label for="identifier" class="text-xs font-semibold text-on-surface-variant block">Email or phone</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">mail</span>
                        <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" required autofocus autocomplete="username"
                            placeholder="name@company.com"
                            class="w-full pl-10 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition">
                    </div>
                </div>

                {{-- Password --}}
                <div class="space-y-2">
                    <label for="password" class="text-xs font-semibold text-on-surface-variant block">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">lock</span>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full pl-10 pr-12 py-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition">
                        <button type="button" onclick="togglePassword()" aria-label="Show password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[20px]" id="password-toggle-icon">visibility</span>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <label class="flex items-center gap-2 cursor-pointer group w-fit">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/20">
                    <span class="text-sm text-on-surface-variant group-hover:text-on-surface transition-colors">Remember me</span>
                </label>

                <button type="submit"
                    class="w-full bg-primary-container hover:bg-primary text-white font-semibold text-base py-3.5 rounded-lg shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.98] transition flex items-center justify-center gap-2">
                    <span>Sign In</span>
                    <span class="material-symbols-outlined text-[20px]">login</span>
                </button>
            </form>

            {{-- Staff SSO — only the providers enabled + configured in admin settings show. --}}
            @php
                $googleOn = setting('social_login', 'google_enabled', false) && filled(setting('social_login', 'google_client_id'));
                $facebookOn = setting('social_login', 'facebook_enabled', false) && filled(setting('social_login', 'facebook_app_id'));
            @endphp
            @if ($googleOn || $facebookOn)
                <div class="mt-6">
                    <div class="relative text-center mb-6">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-outline-variant"></div></div>
                        <span class="relative bg-surface-container-lowest px-3 text-xs text-outline uppercase tracking-wider">or</span>
                    </div>
                    <div class="space-y-3">
                        @if ($googleOn)
                            <a href="{{ route('admin.auth.redirect', 'google') }}"
                                class="w-full flex items-center justify-center gap-3 py-3 rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-on-surface font-medium text-sm transition">
                                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                Sign in with Google
                            </a>
                        @endif
                        @if ($facebookOn)
                            <a href="{{ route('admin.auth.redirect', 'facebook') }}"
                                class="w-full flex items-center justify-center gap-3 py-3 rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-on-surface font-medium text-sm transition">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                                Sign in with Facebook
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Footer --}}
            <div class="mt-8 pt-6 border-t border-outline-variant text-center">
                <p class="text-sm text-on-surface-variant">
                    Not staff? <a href="{{ route('home') }}" class="text-primary font-semibold hover:underline">Return to store</a>
                    — contact your administrator for access.
                </p>
            </div>
        </div>

        {{-- Status bar --}}
        <div class="mt-8 flex items-center justify-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            <span class="text-[10px] text-outline uppercase tracking-wider">System Operational</span>
        </div>
    </main>

    <script>
        function togglePassword() {
            var input = document.getElementById('password'), icon = document.getElementById('password-toggle-icon');
            if (input.type === 'password') { input.type = 'text'; icon.textContent = 'visibility_off'; }
            else { input.type = 'password'; icon.textContent = 'visibility'; }
        }
    </script>
</body>

</html>
