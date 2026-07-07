@extends('layouts.admin')

@section('title', 'Blog posts')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Blog posts</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Blog posts</h2>
        </div>
        @can('blog-posts.create')
            <a href="{{ route('admin.blog.posts.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New post
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total posts" tone="primary" icon="article" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Published" tone="secondary" icon="public" :value="number_format($stats['published'])" />
        <x-admin.stat-card title="Drafts" tone="tertiary" icon="draft" :value="number_format($stats['drafts'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search title…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="status" class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.blog.posts.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>@endif
            <x-admin.per-page :per-page="$perPage" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3"><x-admin.sort-header column="post" label="Post" /></th>
                        <th class="px-6 py-3">Author</th>
                        <th class="px-6 py-3 text-center"><x-admin.sort-header column="categories" label="Categories" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="status" label="Status" /></th>
                        <th class="px-6 py-3"><x-admin.sort-header column="published" label="Published" /></th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-9 rounded-md bg-surface-container-low border border-outline-variant/40 overflow-hidden grid place-items-center shrink-0">
                                        @if ($post->cover)
                                            <img src="{{ $post->cover->url }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-outline text-[18px]">article</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-on-surface truncate max-w-xs">{{ $post->title }}</p>
                                        <p class="text-[11px] text-outline font-mono truncate">/{{ $post->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $post->author?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $post->categories_count }}</td>
                            <td class="px-6 py-3">
                                @if ($post->status === 'published')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">Published</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-container-high text-on-surface-variant">Draft</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-on-surface-variant">{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('blog-posts.edit')
                                        <a href="{{ route('admin.blog.posts.edit', $post) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                                    @endcan
                                    @can('blog-posts.delete')
                                        <form method="POST" action="{{ route('admin.blog.posts.destroy', $post) }}" onsubmit="return confirm('Delete “{{ $post->title }}”?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">article</span>
                                <p class="mt-3 font-semibold text-on-surface">No posts yet</p>
                                <p class="text-sm text-on-surface-variant mt-1">@if (array_filter($filters)) Try clearing the filters. @else <a href="{{ route('admin.blog.posts.create') }}" class="text-primary font-semibold hover:underline">Write your first post</a>. @endif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())<div class="px-6 py-4 border-t border-outline-variant/60"><x-admin.pagination :paginator="$posts" /></div>@endif
    </x-admin.panel>
@endsection
