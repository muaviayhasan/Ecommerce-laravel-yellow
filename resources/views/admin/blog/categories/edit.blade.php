@extends('layouts.admin')

@section('title', 'Edit category · ' . $category->name)

@php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.blog.categories.index') }}" class="text-primary font-semibold hover:underline">Blog categories</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">{{ $category->name }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit category</h2>
    </div>

    <div class="max-w-xl">
        <x-admin.panel>
            <form method="POST" action="{{ route('admin.blog.categories.update', $category) }}" class="space-y-5">
                @csrf @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Name <span class="text-error">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" maxlength="255" class="{{ $cell }}">
                    @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="255" class="{{ $cell }}">
                    @error('slug')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-on-surface-variant">Parent</label>
                        <select name="parent_id" class="{{ $cell }} cursor-pointer">
                            <option value="">None</option>
                            @foreach ($parents as $id => $name)<option value="{{ $id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $id)>{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-on-surface-variant">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" class="{{ $cell }}">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.blog.categories.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Save changes</button>
                </div>
            </form>
        </x-admin.panel>
    </div>
@endsection
