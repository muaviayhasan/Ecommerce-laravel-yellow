@extends('layouts.admin')

@section('title', $meta['title'] . ' · Documentation')

@section('content')
    <div x-data="{ nav: false }">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-label-sm mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                    <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                    <a href="{{ route('admin.docs.index') }}" class="text-primary font-semibold hover:underline">Documentation</a>
                    <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                    <span class="text-on-surface-variant font-semibold">{{ $meta['group'] }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-[32px]">{{ $meta['icon'] ?? 'article' }}</span>
                    <h1 class="text-3xl font-bold text-on-surface">{{ $meta['title'] }}</h1>
                </div>
                @if (! empty($meta['summary']))
                    <p class="text-sm text-on-surface-variant mt-2 max-w-2xl">{{ $meta['summary'] }}</p>
                @endif
            </div>

            {{-- Mobile "contents" toggle --}}
            <button @click="nav = !nav"
                class="lg:hidden inline-flex items-center gap-2 rounded-lg border border-outline-variant px-3 py-2 text-sm font-semibold text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">list</span> Contents
            </button>
        </div>

        <div class="mt-6 flex flex-col lg:flex-row gap-8 items-start">
            {{-- Table of contents --}}
            <aside x-show="nav || window.innerWidth >= 1024" x-cloak
                class="w-full lg:w-64 shrink-0 lg:sticky lg:top-6">
                <div class="rounded-xl bg-surface-container-lowest dark:bg-surface-container p-4 shadow-sm">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest px-2 mb-2">On this handbook</p>
                    <nav class="space-y-4">
                        @foreach ($groups as $group)
                            <div>
                                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wide px-2 mb-1.5">
                                    {{ $group['label'] }}
                                </p>
                                <div class="space-y-0.5">
                                    @foreach ($group['pages'] as $slug => $page)
                                        <a href="{{ route('admin.docs.show', $slug) }}"
                                            class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors
                                                {{ $current === $slug
                                                    ? 'bg-primary-container/20 text-primary font-semibold'
                                                    : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                                            <span class="material-symbols-outlined text-[18px]">{{ $page['icon'] ?? 'chevron_right' }}</span>
                                            <span>{{ $page['title'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- Article --}}
            <article class="min-w-0 flex-1">
                <div class="rounded-xl bg-surface-container-lowest dark:bg-surface-container p-6 lg:p-8 shadow-sm">
                    @include('admin.docs.pages.' . $current)
                </div>

                {{-- Prev / next --}}
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div>
                        @if ($prev)
                            <a href="{{ route('admin.docs.show', $prev['slug']) }}"
                                class="group flex flex-col rounded-xl border border-outline-variant/60 p-4 hover:border-primary/50 transition-colors">
                                <span class="flex items-center gap-1 text-xs text-outline"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Previous</span>
                                <span class="font-semibold text-on-surface group-hover:text-primary mt-1">{{ $prev['title'] }}</span>
                            </a>
                        @endif
                    </div>
                    <div>
                        @if ($next)
                            <a href="{{ route('admin.docs.show', $next['slug']) }}"
                                class="group flex flex-col items-end text-right rounded-xl border border-outline-variant/60 p-4 hover:border-primary/50 transition-colors">
                                <span class="flex items-center gap-1 text-xs text-outline">Next <span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
                                <span class="font-semibold text-on-surface group-hover:text-primary mt-1">{{ $next['title'] }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
