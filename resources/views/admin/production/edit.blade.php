@extends('layouts.admin')

@section('title', 'Edit · ' . $order->production_number)

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.production.index') }}" class="text-primary font-semibold hover:underline">Production</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <a href="{{ route('admin.production.show', $order) }}" class="text-on-surface-variant font-semibold hover:text-primary">{{ $order->production_number }}</a>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit production run</h2>
    </div>

    <form method="POST" action="{{ route('admin.production.update', $order) }}">
        @csrf
        @method('PUT')
        @include('admin.production._form')
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.production.show', $order) }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check</span> Save changes
            </button>
        </div>
    </form>
@endsection
