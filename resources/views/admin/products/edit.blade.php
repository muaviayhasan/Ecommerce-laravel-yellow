@extends('layouts.admin')

@section('title', 'Edit · ' . $product->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.products.index') }}" class="text-primary font-semibold hover:underline">Products</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold line-clamp-1">{{ $product->name }}</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Edit product</h2>
        </div>
        <a href="{{ route('admin.products.show', $product) }}" class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2 shrink-0">
            <span class="material-symbols-outlined text-[20px]">visibility</span> View
        </a>
    </div>

    <form method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('admin.products._form')

        <div class="mt-6 flex items-center justify-between gap-3">
            @can('products.delete')
                <button type="submit" form="delete-product" onclick="return confirm('Delete “{{ $product->name }}”? It will be archived (soft-deleted).');"
                    class="px-4 py-2.5 text-sm font-semibold text-error hover:bg-error-container rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            @else
                <span></span>
            @endcan
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">check</span> Save changes
                </button>
            </div>
        </div>
    </form>

    @can('products.delete')
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" id="delete-product" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endcan
@endsection
