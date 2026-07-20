@extends('layouts.admin')

@section('title', $config['label'] . ' Settings')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Settings</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Settings</h2>
            <p class="text-sm text-on-surface-variant mt-1">Manage store configuration, payments, shipping, tax and more.</p>
        </div>

        {{-- Flash --}}
        @if (session('settings_status'))
            <div class="flex items-center gap-2 bg-secondary-container text-on-secondary-container px-4 py-2.5 rounded-lg text-sm font-medium"
                x-data x-init="setTimeout(() => $el.remove(), 4000)">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                {{ session('settings_status') }}
            </div>
        @endif
        @if (session('settings_error'))
            <div class="flex items-start gap-2 bg-error-container text-on-error-container px-4 py-2.5 rounded-lg text-sm font-medium">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span class="break-words">{{ session('settings_error') }}</span>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-outline-variant">
            @foreach ($tabs as $tab)
                <a href="{{ route('admin.settings.show', $tab['key']) }}"
                    @class([
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px whitespace-nowrap transition-colors',
                        'border-primary text-primary' => $group === $tab['key'],
                        'border-transparent text-on-surface-variant hover:text-primary' => $group !== $tab['key'],
                    ])>
                    <span class="material-symbols-outlined text-[20px]">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($config['sections'] as $section)
                <x-settings.section :title="$section['title']" :description="$section['description'] ?? null">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        @foreach ($section['fields'] as $name => $meta)
                            <div @class([
                                'md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea', 'media'], true),
                            ])>
                                <x-settings.field :group="$group" :name="$name" :meta="$meta"
                                    :value="$values[$name] ?? ($meta['default'] ?? null)" />
                            </div>
                        @endforeach
                    </div>
                </x-settings.section>
            @endforeach

            {{-- Save bar — sticky so it stays visible while the page scrolls. --}}
            <div class="sticky bottom-4 z-20 flex items-center justify-end gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-4 py-3 shadow-lg">
                <a href="{{ route('admin.settings.show', $group) }}"
                    class="px-5 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">
                    Discard
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Save changes
                </button>
            </div>
        </form>

        {{-- Mail: send a test email to verify the SMTP settings above. --}}
        @if ($group === 'mail')
            <x-settings.section title="Send test email"
                description="Save your SMTP settings first, then send a test message to confirm delivery works.">
                <form method="POST" action="{{ route('admin.settings.mail.test') }}"
                    class="flex flex-col sm:flex-row sm:items-start gap-3">
                    @csrf
                    <div class="flex-1 space-y-1.5">
                        <label for="test_email" class="sr-only">Recipient email</label>
                        <input type="email" id="test_email" name="test_email" required maxlength="255"
                            value="{{ old('test_email', auth()->user()->email ?? '') }}"
                            placeholder="you@example.com"
                            class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-3.5 py-2.5 text-sm text-on-surface outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary">
                        @error('test_email')
                            <p class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}
                            </p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center justify-center gap-2 hover:bg-surface-container transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[20px]">send</span>
                        Send test
                    </button>
                </form>
            </x-settings.section>
        @endif
    </div>
@endsection
