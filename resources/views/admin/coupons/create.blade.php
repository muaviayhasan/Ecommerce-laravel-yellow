@extends('layouts.admin')

@section('title', 'Add coupon')

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.coupons.index') }}" class="text-primary font-semibold hover:underline">Coupons</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Add coupon</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Add coupon</h2>
    </div>

    <form method="POST" action="{{ route('admin.coupons.store') }}">
        @csrf
        @include('admin.coupons._form')
        {{-- Sticky action bar — always visible while the form scrolls. --}}
        <div class="sticky bottom-4 z-20 mt-6 flex items-center justify-end gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-4 py-3 shadow-lg">
            <a href="{{ route('admin.coupons.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check</span> Create coupon
            </button>
        </div>
    </form>
@endsection
