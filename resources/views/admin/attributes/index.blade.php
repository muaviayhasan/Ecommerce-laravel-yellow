@extends('layouts.admin')

@section('title', 'Attributes')

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Attributes</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Attributes</h2>
        </div>
        <a href="{{ route('admin.attributes.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">add</span>
            Add attribute
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Attributes" tone="primary" icon="settings_input_component" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Used for variants" tone="secondary" icon="tune" :value="number_format($stats['variation'])" />
        <x-admin.stat-card title="Total values" tone="tertiary" icon="sell" :value="number_format($stats['values'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        {{-- Filter bar --}}
        <form method="GET" class="p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or code…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>

            <select name="type" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="variation" data-no-select2
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any usage</option>
                <option value="yes" @selected(($filters['variation'] ?? '') === 'yes')>Used for variants</option>
                <option value="no" @selected(($filters['variation'] ?? '') === 'no')>Informational</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.attributes.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Attribute</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Values</th>
                        <th class="px-6 py-3">Variants</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($attributes as $attribute)
                        <tr class="hover:bg-surface-container-high/60 transition-colors align-top">
                            <td class="px-6 py-3.5">
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="font-bold text-on-surface hover:text-primary transition-colors">{{ $attribute->name }}</a>
                                <div class="text-[11px] text-outline">{{ $attribute->code }}</div>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full capitalize">{{ $attribute->type }}</span>
                            </td>
                            <td class="px-6 py-3.5 max-w-md">
                                @if ($attribute->values->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($attribute->values->take(8) as $value)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-surface-container-low border border-outline-variant/50 rounded-full text-[11px] text-on-surface-variant">
                                                @if ($value->color_hex)
                                                    <span class="w-2.5 h-2.5 rounded-full border border-outline-variant/50" style="background: {{ $value->color_hex }}"></span>
                                                @endif
                                                {{ $value->label }}
                                            </span>
                                        @endforeach
                                        @if ($attribute->values_count > 8)
                                            <span class="text-[11px] text-outline">+{{ $attribute->values_count - 8 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-outline">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($attribute->is_variation)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Yes</span>
                                @else
                                    <span class="px-2 py-0.5 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">No</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.attributes.edit', $attribute) }}" title="Edit"
                                        class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}"
                                        onsubmit="return confirm('Delete “{{ $attribute->name }}” and its {{ $attribute->values_count }} value(s)? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">settings_input_component</span>
                                <p class="mt-3 font-semibold text-on-surface">No attributes found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters))
                                        Try clearing the filters, or
                                    @endif
                                    <a href="{{ route('admin.attributes.create') }}" class="text-primary font-semibold hover:underline">add your first attribute</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attributes->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$attributes" />
            </div>
        @endif
    </x-admin.panel>
@endsection
