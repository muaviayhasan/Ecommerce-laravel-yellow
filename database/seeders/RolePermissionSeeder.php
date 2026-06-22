<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission groups. Permission name = resource URI (plural) + verb
     * (PROJECT_DOCUMENTATION §14). Add a module's permissions here the moment
     * it's finished, then re-seed.
     *
     * @var array<string, list<string>>
     */
    public static array $groups = [
        'dashboard' => ['view'],
        'products' => ['view', 'create', 'edit', 'delete'],
        'variants' => ['edit'],
        'categories' => ['view', 'create', 'edit', 'delete'],
        'brands' => ['view', 'create', 'edit', 'delete'],
        'attributes' => ['view', 'create', 'edit', 'delete'],
        'media' => ['view', 'create', 'edit', 'delete'],
        'inventory' => ['view', 'edit'],
        'suppliers' => ['view', 'create', 'edit', 'delete'],
        'purchases' => ['view', 'create', 'edit', 'delete'],
        'orders' => ['view', 'edit'],
        'coupons' => ['view', 'create', 'edit', 'delete'],
        'reviews' => ['view', 'moderate'],
        'wishlists' => ['view'],
        'blog-posts' => ['view', 'create', 'edit', 'delete'],
        'blog-categories' => ['view', 'create', 'edit', 'delete'],
        'blog-tags' => ['view', 'create', 'edit', 'delete'],
        'customers' => ['view', 'create', 'edit', 'delete'],
        'ledger' => ['view'],
        'reports' => ['view'],
        'settings' => ['view', 'edit'],
        'roles' => ['view', 'create', 'edit', 'delete'],
    ];

    /**
     * Which permission groups each role receives. `super-admin` gets everything
     * (and also bypasses checks via Gate::before — wire that in a service provider).
     *
     * @var array<string, list<string>>
     */
    public static array $roleGroups = [
        'admin' => ['*'],
        'catalog-manager' => [
            'dashboard', 'products', 'variants', 'categories', 'brands',
            'attributes', 'media', 'inventory', 'suppliers', 'purchases', 'reviews',
        ],
        'order-manager' => ['dashboard', 'orders', 'customers', 'reports'],
        'accountant' => ['dashboard', 'ledger', 'reports', 'orders', 'purchases'],
        'editor' => ['dashboard', 'blog-posts', 'blog-categories', 'blog-tags', 'media'],
        'customer' => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // 1. Create every permission.
        $allPermissions = [];
        foreach (self::$groups as $resource => $actions) {
            foreach ($actions as $action) {
                $name = "{$resource}.{$action}";
                Permission::findOrCreate($name, $guard);
                $allPermissions[] = $name;
            }
        }

        // 2. super-admin — gets all permissions.
        $superAdmin = Role::findOrCreate('super-admin', $guard);
        $superAdmin->syncPermissions($allPermissions);

        // 3. Other roles per their group allow-list.
        foreach (self::$roleGroups as $roleName => $groups) {
            $role = Role::findOrCreate($roleName, $guard);

            $permissions = $groups === ['*']
                ? $allPermissions
                : $this->permissionsForGroups($groups);

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $groups
     * @return list<string>
     */
    private function permissionsForGroups(array $groups): array
    {
        $permissions = [];
        foreach ($groups as $resource) {
            foreach (self::$groups[$resource] ?? [] as $action) {
                $permissions[] = "{$resource}.{$action}";
            }
        }

        return $permissions;
    }
}
