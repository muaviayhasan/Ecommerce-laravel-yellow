@extends('layouts.admin')

@section('title', 'Blog categories')

@php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.blog.posts.index') }}" class="text-primary font-semibold hover:underline">Blog</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Categories</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Blog categories</h2>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        @can('blog-categories.create')
            <div class="col-span-12 lg:col-span-4">
                <x-admin.panel title="Add category">
                    <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Name <span class="text-error">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" maxlength="255" class="{{ $cell }}">
                            @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" maxlength="255" placeholder="Auto from name" class="{{ $cell }}">
                            @error('slug')<p class="text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Parent</label>
                                <select name="parent_id" class="{{ $cell }} cursor-pointer">
                                    <option value="">None</option>
                                    @foreach ($parents as $id => $name)<option value="{{ $id }}" @selected((string) old('parent_id') === (string) $id)>{{ $name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Sort</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="{{ $cell }}">
                            </div>
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Add category</button>
                    </form>
                </x-admin.panel>
            </div>
        @endcan

        <div class="col-span-12 {{ auth()->user()->can('blog-categories.create') ? 'lg:col-span-8' : '' }}">
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr><th class="px-6 py-3">Name</th><th class="px-6 py-3">Parent</th><th class="px-6 py-3 text-center">Posts</th><th class="px-6 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40 text-sm">
                            @forelse ($categories as $category)
                                <tr class="hover:bg-surface-container-high/60 transition-colors">
                                    <td class="px-6 py-3"><p class="font-semibold text-on-surface">{{ $category->name }}</p><p class="text-[11px] text-outline font-mono">{{ $category->slug }}</p></td>
                                    <td class="px-6 py-3 text-on-surface-variant">{{ $category->parent?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-center text-on-surface-variant">{{ $category->posts_count }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('blog-categories.edit')<a href="{{ route('admin.blog.categories.edit', $category) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><span class="material-symbols-outlined text-[20px]">edit</span></a>@endcan
                                            @can('blog-categories.delete')
                                                <form method="POST" action="{{ route('admin.blog.categories.destroy', $category) }}" onsubmit="return confirm('Delete “{{ $category->name }}”?');">@csrf @method('DELETE')<button type="submit" title="Delete" class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error"><span class="material-symbols-outlined text-[20px]">delete</span></button></form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-on-surface-variant"><span class="material-symbols-outlined text-outline" style="font-size:40px;">category</span><p class="mt-2 text-sm">No categories yet.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>
        </div>
    </div>
@endsection
