@extends('layouts.admin')

@section('title', 'Edit · ' . $supplier->name)

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.suppliers.index') }}" class="text-primary font-semibold hover:underline">Suppliers</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">{{ $supplier->name }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit supplier</h2>
    </div>

    <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        @include('admin.suppliers._form')
        <div class="mt-6 flex items-center justify-between gap-3">
            @can('suppliers.delete')
                <button type="submit" form="delete-supplier" onclick="return confirm('Delete “{{ $supplier->name }}”?');"
                    class="px-4 py-2.5 text-sm font-semibold text-error hover:bg-error-container rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            @else
                <span></span>
            @endcan
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.suppliers.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">check</span> Save changes
                </button>
            </div>
        </div>
    </form>

    @can('suppliers.delete')
        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" id="delete-supplier" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endcan
@endsection
