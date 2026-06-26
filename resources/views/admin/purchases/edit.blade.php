@extends('layouts.admin')

@section('title', 'Edit · ' . $purchase->purchase_number)

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.purchases.index') }}" class="text-primary font-semibold hover:underline">Purchases</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <a href="{{ route('admin.purchases.show', $purchase) }}" class="text-on-surface-variant font-semibold hover:text-primary">{{ $purchase->purchase_number }}</a>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit purchase</h2>
    </div>

    <form method="POST" action="{{ route('admin.purchases.update', $purchase) }}">
        @csrf
        @method('PUT')
        @include('admin.purchases._form')
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.purchases.show', $purchase) }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check</span> Save changes
            </button>
        </div>
    </form>
@endsection
