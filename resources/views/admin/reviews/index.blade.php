@extends('layouts.admin')

@section('title', 'Reviews')

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Reviews</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Product reviews</h2>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Total" tone="primary" icon="reviews" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Pending" tone="tertiary" icon="hourglass_top" :value="number_format($stats['pending'])" />
        <x-admin.stat-card title="Approved" tone="secondary" icon="verified" :value="number_format($stats['approved'])" />
        <x-admin.stat-card title="Avg rating" tone="primary" icon="star" :value="$stats['avg'] ?: '—'" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search product, reviewer or text…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
            </select>
            <select name="rating" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any rating</option>
                @for ($r = 5; $r >= 1; $r--)<option value="{{ $r }}" @selected((string) ($filters['rating'] ?? '') === (string) $r)>{{ $r }} star{{ $r > 1 ? 's' : '' }}</option>@endfor
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.reviews.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
        </form>

        <div class="divide-y divide-outline-variant/40">
            @forelse ($reviews as $review)
                <div class="p-5 flex flex-col md:flex-row gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="material-symbols-outlined text-[18px] {{ $i <= $review->rating ? 'text-amber-500' : 'text-outline' }}" style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? 1 : 0 }}">star</span>
                                @endfor
                            </div>
                            @if ($review->verified_purchase)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">verified</span>Verified</span>
                            @endif
                            @if ($review->is_approved)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Approved</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-container text-on-tertiary-container">Pending</span>
                            @endif
                        </div>
                        @if ($review->title)<p class="font-semibold text-on-surface mt-2">{{ $review->title }}</p>@endif
                        <p class="text-sm text-on-surface-variant mt-1 whitespace-pre-line">{{ $review->body }}</p>
                        <p class="text-xs text-outline mt-2">
                            {{ $review->user?->name ?? 'Customer' }} on
                            <span class="font-medium text-on-surface-variant">{{ $review->product?->name ?? 'product' }}</span>
                            · {{ $review->created_at?->format('d M Y') }}
                        </p>
                    </div>

                    @canany(['reviews.moderate'])
                        <div class="flex md:flex-col items-end gap-2 shrink-0">
                            @if ($review->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.unapprove', $review) }}">@csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-high flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">undo</span> Unapprove</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">@csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-on-primary bg-primary rounded-lg hover:brightness-110 flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">check</span> Approve</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review?');">@csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-error rounded-lg flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span> Delete</button>
                            </form>
                        </div>
                    @endcanany
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="material-symbols-outlined text-outline" style="font-size:48px;">reviews</span>
                    <p class="mt-3 font-semibold text-on-surface">No reviews</p>
                    <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else Customer reviews will appear here for moderation. @endif</p>
                </div>
            @endforelse
        </div>

        @if ($reviews->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$reviews" /></div>@endif
    </x-admin.panel>
@endsection
