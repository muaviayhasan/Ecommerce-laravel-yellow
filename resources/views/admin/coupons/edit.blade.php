@extends('layouts.admin')

@section('title', 'Edit · ' . $coupon->code)

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.coupons.index') }}" class="text-primary font-semibold hover:underline">Coupons</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">{{ $coupon->code }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit coupon</h2>
    </div>

    <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
        @csrf
        @method('PUT')
        @include('admin.coupons._form')
        {{-- Sticky action bar — always visible while the form scrolls. --}}
        <div class="sticky bottom-4 z-20 mt-6 flex items-center justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-4 py-3 shadow-lg">
            @can('coupons.delete')
                <button type="submit" form="delete-coupon" onclick="return confirm('Delete coupon “{{ $coupon->code }}”?');"
                    class="px-4 py-2.5 text-sm font-semibold text-error hover:bg-error-container rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            @else
                <span></span>
            @endcan
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.coupons.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">check</span> Save changes
                </button>
            </div>
        </div>
    </form>

    @can('coupons.delete')
        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" id="delete-coupon" class="hidden">@csrf @method('DELETE')</form>
    @endcan
@endsection
