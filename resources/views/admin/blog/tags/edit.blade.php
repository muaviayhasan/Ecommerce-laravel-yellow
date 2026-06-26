@extends('layouts.admin')

@section('title', 'Edit tag · ' . $tag->name)

@php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.blog.tags.index') }}" class="text-primary font-semibold hover:underline">Blog tags</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">{{ $tag->name }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit tag</h2>
    </div>

    <div class="max-w-md">
        <x-admin.panel>
            <form method="POST" action="{{ route('admin.blog.tags.update', $tag) }}" class="space-y-5">
                @csrf @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Name <span class="text-error">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tag->name) }}" maxlength="255" class="{{ $cell }}">
                    @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $tag->slug) }}" maxlength="255" class="{{ $cell }}">
                    @error('slug')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.blog.tags.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Save changes</button>
                </div>
            </form>
        </x-admin.panel>
    </div>
@endsection
