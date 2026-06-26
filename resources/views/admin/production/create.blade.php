@extends('layouts.admin')

@section('title', 'New production run')

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.production.index') }}" class="text-primary font-semibold hover:underline">Production</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">New run</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">New production run</h2>
        <p class="text-sm text-on-surface-variant mt-1">Saved as a draft — completing it consumes components and produces finished stock.</p>
    </div>

    <form method="POST" action="{{ route('admin.production.store') }}">
        @csrf
        @include('admin.production._form')
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.production.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check</span> Create draft
            </button>
        </div>
    </form>
@endsection
