@extends('layouts.admin')

@section('title', 'Error · ' . $log->shortType())

@section('content')
    <div class="mb-4 flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.error-logs.index') }}" class="text-primary font-semibold hover:underline">Error logs</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold truncate">{{ $log->shortType() }}</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="text-2xl font-bold text-on-surface">{{ $log->shortType() }}</h2>
                @php $tone = ['critical' => 'bg-error-container text-on-error-container', 'warning' => 'bg-tertiary-container text-on-tertiary-container'][$log->level] ?? 'bg-surface-container-high text-on-surface-variant'; @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tone }}">{{ $log->level }}</span>
                @if ($log->isResolved())
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Resolved</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container">Open</span>
                @endif
            </div>
            <p class="text-xs text-on-surface-variant mt-1 font-mono break-all">{{ $log->type }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('error-logs.resolve')
                <form method="POST" action="{{ route('admin.error-logs.resolve', $log) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="px-4 py-2.5 font-semibold text-sm rounded-lg flex items-center gap-2 transition-colors {{ $log->isResolved() ? 'border border-outline text-on-surface hover:bg-surface-container' : 'bg-secondary-container text-on-secondary-container hover:brightness-105' }}">
                        <span class="material-symbols-outlined text-[20px]">{{ $log->isResolved() ? 'replay' : 'check_circle' }}</span>
                        {{ $log->isResolved() ? 'Reopen' : 'Mark resolved' }}
                    </button>
                </form>
            @endcan
            @can('error-logs.delete')
                <form method="POST" action="{{ route('admin.error-logs.destroy', $log) }}" onsubmit="return confirm('Delete this error log?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 border border-outline text-on-surface-variant hover:text-error font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Message --}}
    <x-admin.panel class="mb-5">
        <p class="text-on-surface font-medium leading-relaxed break-words whitespace-pre-wrap">{{ $log->message }}</p>
        @if ($log->file)
            <p class="mt-3 text-label-sm text-on-surface-variant font-mono break-all">{{ $log->file }}@if ($log->line):{{ $log->line }}@endif</p>
        @endif
    </x-admin.panel>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Meta --}}
        <x-admin.panel class="lg:col-span-1">
            <h3 class="font-bold mb-3">Details</h3>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Occurrences</dt><dd class="font-semibold">{{ number_format($log->occurrences) }}</dd></div>
                @if ($log->code)<div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Code</dt><dd class="font-mono">{{ $log->code }}</dd></div>@endif
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">First seen</dt><dd class="text-right">{{ format_datetime($log->created_at) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Last seen</dt><dd class="text-right">{{ format_datetime($log->last_seen_at ?? $log->created_at) }}</dd></div>
                @if ($log->method || $log->url)
                    <div class="pt-2 border-t border-outline-variant/50"><dt class="text-on-surface-variant mb-0.5">Request</dt><dd class="font-mono text-xs break-all">{{ $log->method }} {{ $log->url }}</dd></div>
                @endif
                @if ($log->user)<div class="flex justify-between gap-3"><dt class="text-on-surface-variant">User</dt><dd class="text-right">{{ $log->user->name }}</dd></div>@endif
                @if ($log->ip_address)<div class="flex justify-between gap-3"><dt class="text-on-surface-variant">IP</dt><dd class="font-mono">{{ $log->ip_address }}</dd></div>@endif
                @if ($log->isResolved())
                    <div class="pt-2 border-t border-outline-variant/50 flex justify-between gap-3"><dt class="text-on-surface-variant">Resolved by</dt><dd class="text-right">{{ $log->resolver->name ?? '—' }} · {{ format_date($log->resolved_at) }}</dd></div>
                @endif
            </dl>

            @if (! empty($log->context['input']))
                <h3 class="font-bold mt-5 mb-2">Request input</h3>
                <pre class="text-xs bg-surface-container-low rounded-lg p-3 overflow-x-auto text-on-surface-variant">{{ json_encode($log->context['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </x-admin.panel>

        {{-- Stack trace --}}
        <x-admin.panel class="lg:col-span-2 !p-0 overflow-hidden">
            <div class="p-4 border-b border-outline-variant/60"><h3 class="font-bold">Stack trace</h3></div>
            <pre class="text-xs leading-relaxed p-4 overflow-x-auto text-on-surface-variant max-h-[32rem]">{{ $log->trace ?: 'No stack trace captured.' }}</pre>
        </x-admin.panel>
    </div>
@endsection
