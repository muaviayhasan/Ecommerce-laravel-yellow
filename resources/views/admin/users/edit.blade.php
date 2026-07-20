@extends('layouts.admin')

@section('title', 'Edit ' . $user->name)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.users.index') }}" class="text-primary font-semibold hover:underline">Users</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold truncate">{{ $user->name }}</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Edit user</h2>
        </div>

        @if ($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                onsubmit="return confirm('Delete “{{ $user->name }}”? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2.5 border border-error/40 text-error font-semibold text-sm rounded-lg hover:bg-error-container/40 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">delete</span> Delete
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.users._form')

        {{-- Sticky action bar — always visible while the form scrolls. --}}
        <div class="sticky bottom-4 z-20 flex items-center justify-end gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest dark:bg-surface-container px-4 py-3 shadow-lg">
            <a href="{{ route('admin.users.index') }}"
                class="px-5 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">Cancel</a>
            <button type="submit"
                class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
                <span class="material-symbols-outlined text-[20px]">save</span> Save changes
            </button>
        </div>
    </form>
@endsection
