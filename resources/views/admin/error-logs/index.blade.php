@extends('layouts.admin')

@section('title', 'Error logs')

@section('content')
    <div class="mb-2 flex items-end justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Error logs</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Error logs</h2>
            <p class="text-sm text-on-surface-variant mt-1">Unhandled exceptions captured from across the system. Toggle capture in
                <a href="{{ route('admin.settings.show', 'system') }}" class="text-primary font-semibold hover:underline">Settings → System</a>.
            </p>
        </div>
        @can('error-logs.delete')
            @if ($stats['total'] > $stats['open'])
                <form method="POST" action="{{ route('admin.error-logs.clear') }}" onsubmit="return confirm('Delete all resolved error logs?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-[20px]">delete_sweep</span> Clear resolved
                    </button>
                </form>
            @endif
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Open" tone="primary" icon="bug_report" :value="number_format($stats['open'])" />
        <x-admin.stat-card title="Critical (open)" tone="tertiary" icon="priority_high" :value="number_format($stats['critical'])" />
        <x-admin.stat-card title="Last 24h" tone="secondary" icon="schedule" :value="number_format($stats['today'])" />
        <x-admin.stat-card title="Total" tone="primary" icon="database" :value="number_format($stats['total'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search message, type or URL…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Resolved</option>
            </select>
            <select name="level" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any level</option>
                <option value="critical" @selected(($filters['level'] ?? '') === 'critical')>Critical</option>
                <option value="error" @selected(($filters['level'] ?? '') === 'error')>Error</option>
                <option value="warning" @selected(($filters['level'] ?? '') === 'warning')>Warning</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.error-logs.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
            <x-admin.per-page :per-page="$perPage" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/60">
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="type" label="Error" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="level" label="Level" /></th>
                        <th class="px-5 py-3 font-semibold text-right"><x-admin.sort-header column="occurrences" label="Count" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="last_seen" label="Last seen" /></th>
                        <th class="px-5 py-3 font-semibold"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-surface-container-low/50">
                            <td class="px-5 py-3 max-w-md">
                                <a href="{{ route('admin.error-logs.show', $log) }}" class="block">
                                    <span class="font-semibold text-on-surface">{{ $log->shortType() }}</span>
                                    <span class="block text-xs text-on-surface-variant truncate">{{ $log->message }}</span>
                                    @if ($log->url)<span class="block text-[11px] text-outline truncate">{{ $log->method }} {{ $log->url }}</span>@endif
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                @php $tone = ['critical' => 'bg-error-container text-on-error-container', 'warning' => 'bg-tertiary-container text-on-tertiary-container'][$log->level] ?? 'bg-surface-container-high text-on-surface-variant'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tone }}">{{ $log->level }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold tabular-nums">{{ number_format($log->occurrences) }}</td>
                            <td class="px-5 py-3 text-on-surface-variant whitespace-nowrap">{{ format_datetime($log->last_seen_at ?? $log->created_at) }}</td>
                            <td class="px-5 py-3">
                                @if ($log->isResolved())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Resolved</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Open</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.error-logs.show', $log) }}" class="px-2 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-primary rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span></a>
                                @can('error-logs.resolve')
                                    <form method="POST" action="{{ route('admin.error-logs.resolve', $log) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-2 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-secondary rounded-lg inline-flex items-center gap-1" title="{{ $log->isResolved() ? 'Reopen' : 'Mark resolved' }}">
                                            <span class="material-symbols-outlined text-[16px]">{{ $log->isResolved() ? 'replay' : 'check_circle' }}</span>
                                        </button>
                                    </form>
                                @endcan
                                @can('error-logs.delete')
                                    <form method="POST" action="{{ route('admin.error-logs.destroy', $log) }}" onsubmit="return confirm('Delete this error log?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-error rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-secondary" style="font-size:48px;">check_circle</span>
                                <p class="mt-3 font-semibold text-on-surface">No errors logged</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Captured exceptions will appear here. A quiet page is a good sign. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$logs" /></div>@endif
    </x-admin.panel>
@endsection
