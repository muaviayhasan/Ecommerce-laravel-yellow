@extends('layouts.admin')

@section('title', 'Newsletter subscribers')

@section('content')
    <div class="mb-2 flex items-end justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Subscribers</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Newsletter subscribers</h2>
        </div>
        @can('subscribers.export')
            <a href="{{ route('admin.subscribers.export') }}"
                class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">download</span> Export CSV
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="group" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="mark_email_read" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Unsubscribed" tone="tertiary" icon="unsubscribe" :value="number_format($stats['unsubscribed'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search email or name…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="unsubscribed" @selected(($filters['status'] ?? '') === 'unsubscribed')>Unsubscribed</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.subscribers.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/60">
                        <th class="px-5 py-3 font-semibold">Email</th>
                        <th class="px-5 py-3 font-semibold">Name</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold">Subscribed</th>
                        <th class="px-5 py-3 font-semibold">Source</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse ($subscribers as $subscriber)
                        <tr class="hover:bg-surface-container-low/50">
                            <td class="px-5 py-3 font-medium text-on-surface">{{ $subscriber->email }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $subscriber->name ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($subscriber->unsubscribed_at)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-container text-on-tertiary-container">Unsubscribed</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Active</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ format_date($subscriber->subscribed_at ?? $subscriber->created_at) }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $subscriber->source ?: '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                @can('subscribers.delete')
                                    <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('Remove this subscriber?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-error rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span> Remove</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">mail</span>
                                <p class="mt-3 font-semibold text-on-surface">No subscribers yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Newsletter signups from the storefront will appear here. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscribers->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$subscribers" /></div>@endif
    </x-admin.panel>
@endsection
