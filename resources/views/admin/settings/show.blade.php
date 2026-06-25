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
                                'md:col-span-2' => in_array($meta['input'] ?? 'text', ['toggle', 'textarea'], true),
                            ])>
                                <x-settings.field :group="$group" :name="$name" :meta="$meta"
                                    :value="$values[$name] ?? ($meta['default'] ?? null)" />
                            </div>
                        @endforeach
                    </div>
                </x-settings.section>
            @endforeach

            {{-- Save bar --}}
            <div class="flex justify-end gap-3">
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
    </div>
@endsection
