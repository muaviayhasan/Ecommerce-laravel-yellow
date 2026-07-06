@extends('layouts.admin')

@section('title', 'Hero Slides')

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Hero Slides</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Hero Slides</h2>
            <p class="text-sm text-on-surface-variant mt-1">Slides shown in the rotating banner at the top of the storefront home page.</p>
        </div>
        <a href="{{ route('admin.hero-slides.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">add</span>
            Add slide
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <x-admin.stat-card title="Total slides" tone="primary" icon="view_carousel" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Slide</th>
                        <th class="px-6 py-3">Highlight</th>
                        <th class="px-6 py-3">Button</th>
                        <th class="px-6 py-3 text-center">Sort</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($slides as $slide)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-10 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden flex items-center justify-center shrink-0">
                                        @if ($slide->image_url)
                                            <img src="{{ $slide->image_url }}" alt="{{ $slide->image_alt }}" class="w-full h-full object-contain">
                                        @else
                                            <span class="material-symbols-outlined text-outline text-[20px]">image</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-on-surface truncate">{{ $slide->line1 }} {{ $slide->line2 }}</div>
                                        @if ($slide->kicker)
                                            <div class="text-[11px] text-outline uppercase tracking-widest truncate">{{ $slide->kicker }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">
                                {{ trim(($slide->tail ? $slide->tail . ' ' : '') . $slide->highlight) ?: '—' }}
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $slide->cta_label ?: '—' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $slide->sort_order }}</td>
                            <td class="px-6 py-3">
                                @if ($slide->is_active)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.hero-slides.edit', $slide) }}" title="Edit"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide) }}"
                                        onsubmit="return confirm('Delete this slide? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">view_carousel</span>
                                <p class="mt-3 font-semibold text-on-surface">No hero slides yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    <a href="{{ route('admin.hero-slides.create') }}" class="text-primary font-semibold hover:underline">Add your first slide</a>
                                    to fill the home page banner.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.panel>
@endsection
