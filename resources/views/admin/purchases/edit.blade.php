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

    <form method="POST" action="{{ route('admin.purchases.update', $purchase) }}" x-data="purchaseForm" @submit.prevent="submit($el)">
        @csrf
        @method('PUT')
        @include('admin.purchases._form')
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.purchases.show', $purchase) }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                <span class="material-symbols-outlined text-[20px]" :class="submitting && 'animate-spin'" x-text="submitting ? 'progress_activity' : 'check'">check</span>
                <span x-text="submitting ? 'Saving…' : 'Save changes'">Save changes</span>
            </button>
        </div>
    </form>
@endsection
