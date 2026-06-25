@extends('layouts.admin')

@section('title', 'Edit ' . $attribute->name)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.attributes.index') }}" class="text-primary font-semibold hover:underline">Attributes</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold truncate">{{ $attribute->name }}</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Edit attribute</h2>
        </div>

        <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}"
            onsubmit="return confirm('Delete “{{ $attribute->name }}” and its values? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="px-4 py-2.5 border border-error/40 text-error font-semibold text-sm rounded-lg hover:bg-error-container/40 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">delete</span>
                Delete
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.attributes._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.attributes.index') }}"
                class="px-5 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">Cancel</a>
            <button type="submit"
                class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
                <span class="material-symbols-outlined text-[20px]">save</span>
                Save changes
            </button>
        </div>
    </form>
@endsection
