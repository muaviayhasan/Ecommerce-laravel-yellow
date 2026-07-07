@extends('layouts.admin')

@section('title', 'Blog comments')

@php
    $tabs = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved'];
    $active = $filters['status'] ?? '';
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.blog.posts.index') }}" class="text-primary font-semibold hover:underline">Blog</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Comments</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Blog comments</h2>
            <p class="text-sm text-on-surface-variant mt-1">Approve, reply to or remove visitor comments. Your replies show under the comment on the post.</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total comments" tone="primary" icon="forum" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Pending" tone="tertiary" icon="schedule" :value="number_format($stats['pending'])" />
        <x-admin.stat-card title="Approved" tone="secondary" icon="check_circle" :value="number_format($stats['approved'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        {{-- Status tabs --}}
        <div class="flex items-center gap-1 p-3 border-b border-outline-variant/60">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.blog.comments.index', array_filter(['status' => $key])) }}"
                    class="px-4 py-1.5 rounded-full text-sm font-semibold transition-colors {{ $active === $key ? 'bg-primary-container text-white' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    {{ $label }}
                    @if ($key === 'pending' && $stats['pending'] > 0)
                        <span class="ml-1 text-[11px]">({{ $stats['pending'] }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="divide-y divide-outline-variant/40">
            @forelse ($comments as $comment)
                <div class="p-5" x-data="{ replyOpen: false }">
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high text-on-surface-variant grid place-items-center font-bold shrink-0">
                            {{ strtoupper(substr($comment->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-on-surface">{{ $comment->name }}</span>
                                <span class="text-[11px] text-outline">{{ $comment->email }}</span>
                                @if ($comment->is_approved)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 bg-tertiary-container text-on-tertiary-container text-[10px] font-bold rounded-full">Pending</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-outline mt-0.5">
                                {{ $comment->created_at->format('d M Y, g:i a') }}
                                · on <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank" class="text-primary hover:underline">{{ \Illuminate\Support\Str::limit($comment->post->title, 48) }}</a>
                            </p>
                            <p class="text-sm text-on-surface mt-2 whitespace-pre-line break-words">{{ $comment->body }}</p>

                            {{-- Actions --}}
                            <div class="flex flex-wrap items-center gap-1.5 mt-3">
                                @can('blog-comments.moderate')
                                    <form method="POST" action="{{ route('admin.blog.comments.approve', $comment) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-outline-variant hover:bg-surface-container-high transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">{{ $comment->is_approved ? 'visibility_off' : 'check' }}</span>
                                            {{ $comment->is_approved ? 'Unapprove' : 'Approve' }}
                                        </button>
                                    </form>
                                @endcan
                                @can('blog-comments.reply')
                                    <button type="button" @click="replyOpen = !replyOpen" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-outline-variant hover:bg-surface-container-high transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">reply</span> Reply
                                    </button>
                                @endcan
                                @can('blog-comments.delete')
                                    <form method="POST" action="{{ route('admin.blog.comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment and its replies?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-error/40 text-error hover:bg-error-container/40 transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>

                            {{-- Reply form --}}
                            @can('blog-comments.reply')
                                <form method="POST" action="{{ route('admin.blog.comments.reply', $comment) }}" x-show="replyOpen" x-cloak x-collapse class="mt-3">
                                    @csrf
                                    <textarea name="body" rows="3" required maxlength="2000" placeholder="Write a reply as {{ auth()->user()->name }}…"
                                        class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary focus:border-primary">{{ old('body') }}</textarea>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" @click="replyOpen = false" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:text-primary">Cancel</button>
                                        <button type="submit" class="px-4 py-1.5 bg-primary text-on-primary text-xs font-bold rounded-lg hover:brightness-110 transition-all">Post reply</button>
                                    </div>
                                </form>
                            @endcan

                            {{-- Replies --}}
                            @if ($comment->replies->isNotEmpty())
                                <div class="mt-4 space-y-3 border-l-2 border-primary-container/40 pl-4">
                                    @foreach ($comment->replies as $reply)
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container grid place-items-center text-xs font-bold shrink-0">
                                                {{ strtoupper(substr($reply->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-bold text-sm text-on-surface">{{ $reply->name }}</span>
                                                    @if ($reply->is_admin)
                                                        <span class="px-2 py-0.5 bg-primary-container text-white text-[10px] font-bold rounded-full">Staff</span>
                                                    @endif
                                                    <span class="text-[11px] text-outline">{{ $reply->created_at->format('d M Y') }}</span>
                                                </div>
                                                <p class="text-sm text-on-surface-variant mt-1 whitespace-pre-line break-words">{{ $reply->body }}</p>
                                            </div>
                                            @can('blog-comments.delete')
                                                <form method="POST" action="{{ route('admin.blog.comments.destroy', $reply) }}" onsubmit="return confirm('Delete this reply?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Delete reply" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="material-symbols-outlined text-outline" style="font-size:48px;">forum</span>
                    <p class="mt-3 font-semibold text-on-surface">No {{ $active ?: '' }} comments</p>
                    <p class="text-sm text-on-surface-variant mt-1">Visitor comments on your blog posts will appear here.</p>
                </div>
            @endforelse
        </div>

        @if ($comments->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$comments" />
            </div>
        @endif
    </x-admin.panel>
@endsection
