@extends('layouts.admin')

@section('title', 'Edit campaign')

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.campaigns.index') }}" class="text-primary font-semibold hover:underline">Campaigns</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">{{ $campaign->subject }}</h2>
        </div>
        @if ($campaign->status === 'sent')
            <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                Sent to {{ number_format($campaign->sent_count) }} · {{ format_datetime($campaign->sent_at) }}
            </span>
        @endif
    </div>

    @if (! $campaign->isEditable())
        <x-admin.panel>
            <p class="text-sm text-on-surface-variant">This campaign has been sent and can no longer be edited. Create a new campaign to send again.</p>
        </x-admin.panel>
    @else
        <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
            @csrf @method('PUT')
            @include('admin.campaigns._form')
            <div class="mt-6 flex items-center justify-between gap-3 flex-wrap">
                @can('campaigns.delete')
                    <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-error transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                        </button>
                    </form>
                @endcan
                <div class="flex items-center gap-3 ml-auto">
                    <button type="submit" class="px-6 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">save</span> Save
                    </button>
                </div>
            </div>
        </form>

        @can('campaigns.send')
            <x-admin.panel class="mt-6">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="font-semibold text-on-surface">Send this campaign</p>
                        <p class="text-sm text-on-surface-variant mt-0.5">Save your changes first, then send to <strong>{{ $campaign->audienceLabel() }}</strong>. This can’t be undone.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}" onsubmit="return confirm('Send now to {{ $campaign->audienceLabel() }}?');">
                        @csrf
                        <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">send</span> Send now
                        </button>
                    </form>
                </div>
            </x-admin.panel>
        @endcan
    @endif
@endsection
