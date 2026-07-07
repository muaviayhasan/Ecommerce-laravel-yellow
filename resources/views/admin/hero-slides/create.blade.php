@extends('layouts.admin')

@section('title', 'New hero slide')

@section('content')
    <div>
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.hero-slides.index') }}" class="text-primary font-semibold hover:underline">Hero Slides</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">New</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">New hero slide</h2>
    </div>

    <form method="POST" action="{{ route('admin.hero-slides.store') }}" class="space-y-6">
        @csrf
        @include('admin.hero-slides._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hero-slides.index') }}"
                class="px-5 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">Cancel</a>
            <button type="submit"
                class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
                <span class="material-symbols-outlined text-[20px]">save</span>
                Create slide
            </button>
        </div>
    </form>
@endsection
