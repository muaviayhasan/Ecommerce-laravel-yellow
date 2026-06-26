<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    /** The built-in super-role is managed by the seeder and bypasses every gate. */
    private const PROTECTED = 'super-admin';

    public static function middleware(): array
    {
        return [
            new Middleware('can:roles.view', only: ['index']),
            new Middleware('can:roles.create', only: ['create', 'store']),
            new Middleware('can:roles.edit', only: ['edit', 'update']),
            new Middleware('can:roles.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->where('guard_name', 'web')
                ->withCount('permissions', 'users')
                ->orderBy('name')
                ->get(),
            'protected' => self::PROTECTED,
            'stats' => [
                'roles' => Role::where('guard_name', 'web')->count(),
                'permissions' => Permission::where('guard_name', 'web')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'groups' => $this->permissionGroups(),
            'assigned' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->name === self::PROTECTED, 403, 'The super-admin role is managed by the system.');

        return view('admin.roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === self::PROTECTED, 403, 'The super-admin role is managed by the system.');

        $data = $request->validated();
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === self::PROTECTED) {
            return back()->with('error', 'The super-admin role cannot be deleted.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'This role is still assigned to users — reassign them first.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * All permissions grouped by their resource prefix, for the checkbox matrix.
     *
     * @return array<string, array{label: string, permissions: array<int, array{name: string, action: string}>}>
     */
    private function permissionGroups(): array
    {
        return Permission::where('guard_name', 'web')->orderBy('name')->get()
            ->groupBy(fn (Permission $p) => Str::beforeLast($p->name, '.'))
            ->map(fn ($items, $resource) => [
                'label' => Str::headline(str_replace('-', ' ', (string) $resource)),
                'permissions' => $items->map(fn (Permission $p) => [
                    'name' => $p->name,
                    'action' => Str::afterLast($p->name, '.'),
                ])->values()->all(),
            ])->all();
    }
}
