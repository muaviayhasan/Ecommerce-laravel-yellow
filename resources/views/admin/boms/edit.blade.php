@extends('layouts.admin')

@section('title', 'Edit BOM')

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.boms.index') }}" class="text-primary font-semibold hover:underline">BOMs</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <a href="{{ route('admin.boms.show', $bom) }}" class="text-on-surface-variant font-semibold hover:text-primary">{{ $bom->product?->name }}</a>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit BOM</h2>
    </div>

    <form method="POST" action="{{ route('admin.boms.update', $bom) }}">
        @csrf
        @method('PUT')
        @include('admin.boms._form')
        <div class="mt-6 flex items-center justify-between gap-3">
            @can('boms.delete')
                <button type="submit" form="delete-bom" onclick="return confirm('Delete this BOM?');" class="px-4 py-2.5 text-sm font-semibold text-error hover:bg-error-container rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            @else
                <span></span>
            @endcan
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.boms.show', $bom) }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">check</span> Save changes
                </button>
            </div>
        </div>
    </form>

    @can('boms.delete')
        <form method="POST" action="{{ route('admin.boms.destroy', $bom) }}" id="delete-bom" class="hidden">@csrf @method('DELETE')</form>
    @endcan
@endsection
