@extends('layouts.admin')

@section('title', 'Activity log')

@php
    $eventStyles = [
        'created' => 'bg-secondary-container text-on-secondary-container',
        'updated' => 'bg-primary-container text-on-primary-container',
        'deleted' => 'bg-error-container text-on-error-container',
    ];
@endphp

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Activity log</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Activity log</h2>
        <p class="text-sm text-on-surface-variant mt-1">Every create, update and delete made by an admin.</p>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <x-admin.stat-card title="Total events" tone="primary" icon="history" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Today" tone="secondary" icon="today" :value="number_format($stats['today'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search description…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="event" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any event</option>
                @foreach (['created', 'updated', 'deleted'] as $e)<option value="{{ $e }}" @selected(($filters['event'] ?? '') === $e)>{{ ucfirst($e) }}</option>@endforeach
            </select>
            <select name="user" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any user</option>
                @foreach ($users as $id => $name)<option value="{{ $id }}" @selected((string) ($filters['user'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.activity.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="divide-y divide-outline-variant/40">
            @forelse ($logs as $log)
                <div class="p-4" x-data="{ open: false }">
                    <div class="flex items-start gap-4">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize shrink-0 mt-0.5 {{ $eventStyles[$log->event] ?? 'bg-surface-container-high text-on-surface-variant' }}">{{ $log->event }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-on-surface font-medium">{{ $log->description }}</p>
                            <p class="text-xs text-outline mt-0.5">
                                {{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->format('d M Y, H:i') }}
                                @if ($log->ip_address) · <span class="font-mono">{{ $log->ip_address }}</span> @endif
                            </p>
                        </div>
                        @if (! empty($log->properties))
                            <button type="button" @click="open = !open" class="text-xs font-semibold text-primary hover:underline shrink-0 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]" x-text="open ? 'expand_less' : 'expand_more'">expand_more</span> Changes
                            </button>
                        @endif
                    </div>

                    @if (! empty($log->properties))
                        @php
                            $after = $log->properties['after'] ?? [];
                            $before = $log->properties['before'] ?? [];
                            $keys = array_keys($after ?: $before);
                        @endphp
                        <div x-show="open" x-cloak class="mt-3 ml-12 overflow-x-auto">
                            <table class="text-xs border border-outline-variant/50 rounded-lg overflow-hidden">
                                <thead class="bg-surface-container-low/60 text-outline uppercase tracking-wider text-[10px]">
                                    <tr><th class="px-3 py-1.5 text-left">Field</th><th class="px-3 py-1.5 text-left">Before</th><th class="px-3 py-1.5 text-left">After</th></tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30">
                                    @foreach ($keys as $key)
                                        <tr>
                                            <td class="px-3 py-1.5 font-mono text-on-surface-variant">{{ $key }}</td>
                                            <td class="px-3 py-1.5 text-error/80 font-mono max-w-xs truncate">{{ is_scalar($before[$key] ?? null) ? ($before[$key] ?? '—') : json_encode($before[$key] ?? null) }}</td>
                                            <td class="px-3 py-1.5 text-secondary font-mono max-w-xs truncate">{{ is_scalar($after[$key] ?? null) ? ($after[$key] ?? '—') : json_encode($after[$key] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="material-symbols-outlined text-outline" style="font-size:48px;">history</span>
                    <p class="mt-3 font-semibold text-on-surface">No activity yet</p>
                    <p class="text-sm text-on-surface-variant mt-1">Admin changes to products, orders, customers and more will be recorded here.</p>
                </div>
            @endforelse
        </div>

        @if ($logs->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$logs" /></div>@endif
    </x-admin.panel>
@endsection
