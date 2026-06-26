@extends('layouts.admin')

@section('title', 'Roles & permissions')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Roles</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Roles &amp; permissions</h2>
        </div>
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}"
                class="px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add</span> New role
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 gap-6">
        <x-admin.stat-card title="Roles" tone="primary" icon="admin_panel_settings" :value="number_format($stats['roles'])" />
        <x-admin.stat-card title="Permissions" tone="secondary" icon="key" :value="number_format($stats['permissions'])" />
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                    <tr>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3 text-center">Permissions</th>
                        <th class="px-6 py-3 text-center">Users</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-sm">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-on-surface capitalize">{{ str_replace('-', ' ', $role->name) }}</span>
                                    @if ($role->name === $protected)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary-container text-on-tertiary-container">System</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ $role->name === $protected ? 'All' : number_format($role->permissions_count) }}</td>
                            <td class="px-6 py-3 text-center text-on-surface-variant">{{ number_format($role->users_count) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($role->name === $protected)
                                        <span class="text-xs text-outline italic pr-2">Managed by system</span>
                                    @else
                                        @can('roles.edit')
                                            <a href="{{ route('admin.roles.edit', $role) }}" title="Edit" class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                                        @endcan
                                        @can('roles.delete')
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete the “{{ $role->name }}” role?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Delete" class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.panel>
@endsection
