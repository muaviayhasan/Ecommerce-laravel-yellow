@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Users</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Users &amp; roles</h2>
        </div>
        <a href="{{ route('admin.users.create') }}"
            class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined">person_add</span> Add user
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-admin.stat-card title="Total users" tone="primary" icon="group" :value="number_format($stats['total'])" />
        <x-admin.stat-card title="Active" tone="secondary" icon="check_circle" :value="number_format($stats['active'])" />
        <x-admin.stat-card title="Admins" tone="tertiary" icon="admin_panel_settings" :value="number_format($stats['admins'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <form method="GET" class="js-filters p-4 border-b border-outline-variant/60 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email or phone…" maxlength="255"
                    class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
            </div>
            <select name="role"
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucwords(str_replace('-', ' ', $role)) }}</option>
                @endforeach
            </select>
            <select name="status"
                class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-container text-white text-sm font-semibold rounded-lg hover:brightness-110 transition-all">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Roles</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Last login</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @forelse ($users as $user)
                        <tr class="hover:bg-surface-container-high/60 transition-colors align-top">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($user->avatar)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shrink-0">
                                    @else
                                        <span class="w-9 h-9 rounded-full bg-primary-container text-white grid place-items-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="font-bold text-on-surface hover:text-primary transition-colors block truncate">{{ $user->name }}</a>
                                        <div class="text-[11px] text-outline truncate">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @forelse ($user->roles as $role)
                                    <span class="inline-block px-2 py-0.5 mb-1 mr-1 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full capitalize">{{ str_replace('-', ' ', $role->name) }}</span>
                                @empty
                                    <span class="text-outline text-xs">—</span>
                                @endforelse
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($user->is_active)
                                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-error-container text-on-error-container text-[10px] font-bold rounded-full">Disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-on-surface-variant">{{ $user->last_login_at ? format_date($user->last_login_at) : 'Never' }}</td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.users.edit', $user) }}" title="Edit"
                                        class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Delete “{{ $user->name }}”? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete"
                                                class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/50 hover:text-error transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-outline" style="font-size:48px;">group</span>
                                <p class="mt-3 font-semibold text-on-surface">No users found</p>
                                <p class="text-sm text-on-surface-variant mt-1">
                                    @if (array_filter($filters)) Try clearing the filters, or @endif
                                    <a href="{{ route('admin.users.create') }}" class="text-primary font-semibold hover:underline">add a user</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60">
                <x-admin.pagination :paginator="$users" />
            </div>
        @endif
    </x-admin.panel>
@endsection
