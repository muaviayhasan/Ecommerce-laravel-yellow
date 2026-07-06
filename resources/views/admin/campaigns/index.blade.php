@extends('layouts.admin')

@section('title', 'Email campaigns')

@section('content')
    <div class="mb-2 flex items-end justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Campaigns</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Email campaigns</h2>
        </div>
        @can('campaigns.create')
            <a href="{{ route('admin.campaigns.create') }}"
                class="px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
                <span class="material-symbols-outlined text-[20px]">add</span> New campaign
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="campaign" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Sent" tone="secondary" icon="mark_email_read" :value="number_format($stats['sent'])" />
        <x-admin.stat-card title="Scheduled" tone="tertiary" icon="schedule_send" :value="number_format($stats['scheduled'])" />
        <x-admin.stat-card title="Emails delivered" tone="primary" icon="send" :value="number_format($stats['recipients'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search subject…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                @foreach (['draft', 'scheduled', 'sending', 'sent'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.campaigns.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/60">
                        <th class="px-5 py-3 font-semibold">Subject</th>
                        <th class="px-5 py-3 font-semibold">Audience</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold">Sent</th>
                        <th class="px-5 py-3 font-semibold">When</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse ($campaigns as $campaign)
                        @php
                            $badge = [
                                'draft' => 'bg-surface-container-high text-on-surface-variant',
                                'scheduled' => 'bg-tertiary-container text-on-tertiary-container',
                                'sending' => 'bg-primary-container text-on-primary-container',
                                'sent' => 'bg-secondary-container text-on-secondary-container',
                            ][$campaign->status] ?? 'bg-surface-container-high text-on-surface-variant';
                        @endphp
                        <tr class="hover:bg-surface-container-low/50">
                            <td class="px-5 py-3 font-medium text-on-surface">
                                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="hover:text-primary">{{ $campaign->subject }}</a>
                                @if ($campaign->coupon)<span class="ml-2 text-xs text-outline">🎟 {{ $campaign->coupon->code }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $campaign->audienceLabel() }}</td>
                            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge }}">{{ ucfirst($campaign->status) }}</span></td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $campaign->sent_count ? number_format($campaign->sent_count) : '—' }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">
                                @if ($campaign->status === 'sent') {{ format_date($campaign->sent_at) }}
                                @elseif ($campaign->scheduled_at) {{ format_datetime($campaign->scheduled_at) }}
                                @else — @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($campaign->isEditable())
                                        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-primary rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">edit</span> Edit</a>
                                        @can('campaigns.send')
                                            <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}" onsubmit="return confirm('Send this campaign now to {{ $campaign->audienceLabel() }}?');" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-on-primary bg-primary rounded-lg hover:brightness-110 inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">send</span> Send</button>
                                            </form>
                                        @endcan
                                    @endif
                                    @can('campaigns.delete')
                                        <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-error rounded-lg inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">campaign</span>
                                <p class="mt-3 font-semibold text-on-surface">No campaigns yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">Create a campaign to email a promotion or newsletter to your customers.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($campaigns->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$campaigns" /></div>@endif
    </x-admin.panel>
@endsection
