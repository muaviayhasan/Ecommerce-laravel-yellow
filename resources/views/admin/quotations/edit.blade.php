@extends('layouts.admin')

@section('title', 'Edit · ' . $quotation->quotation_number)

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.quotations.index') }}" class="text-primary font-semibold hover:underline">Quotations</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <a href="{{ route('admin.quotations.show', $quotation) }}" class="text-primary font-semibold hover:underline">{{ $quotation->quotation_number }}</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Edit</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit quotation</h2>
    </div>

    <form method="POST" action="{{ route('admin.quotations.update', $quotation) }}">
        @csrf
        @method('PUT')
        @include('admin.quotations._form')
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.quotations.show', $quotation) }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check</span> Save changes
            </button>
        </div>
    </form>
@endsection
