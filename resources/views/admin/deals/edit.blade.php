@extends('layouts.admin')

@section('title', 'Edit · ' . $deal->name)

@section('content')
    <div>
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.deals.index') }}" class="text-primary font-semibold hover:underline">Deals</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold line-clamp-1">{{ $deal->name }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Edit deal</h2>
    </div>

    <form method="POST" action="{{ route('admin.deals.update', $deal) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.deals._form')

        {{-- Sticky action bar — always visible while the form scrolls. --}}
        <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-4 py-3 shadow-lg">
            @can('deals.delete')
                <button type="button" x-data
                    @click="$store.pageConfirm.ask(@js('Delete this deal?'), @js('“' . $deal->name . '” will be removed. Products themselves are not affected.'), () => window.__postForm(@js(route('admin.deals.destroy', $deal)), {}, 'DELETE'), 'delete_forever')"
                    class="px-4 py-2.5 text-sm font-semibold text-error hover:bg-error-container rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            @else
                <span></span>
            @endcan
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.deals.index') }}" class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span> Save changes
                </button>
            </div>
        </div>
    </form>

    <x-admin.confirm-modal />
@endsection
