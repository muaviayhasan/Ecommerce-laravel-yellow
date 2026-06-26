<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.roles.index'))
            ->assertOk()->assertSee('Roles');
    }

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        $name = 'Tester ' . uniqid();

        $this->actingAs($this->admin())->post(route('admin.roles.store'), [
            'name' => $name,
            'permissions' => ['products.view', 'orders.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', $name)->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('products.view'));
        $this->assertSame(2, $role->permissions()->count());
    }

    public function test_updating_a_role_syncs_its_permissions(): void
    {
        $role = Role::create(['name' => 'Editor ' . uniqid(), 'guard_name' => 'web']);
        $role->syncPermissions(['products.view']);

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => ['orders.view', 'reports.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('products.view'));
        $this->assertTrue($role->hasPermissionTo('orders.view'));
        $this->assertSame(2, $role->permissions()->count());
    }

    public function test_the_super_admin_role_cannot_be_edited(): void
    {
        $super = Role::where('name', 'super-admin')->firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.roles.edit', $super))->assertForbidden();
    }

    public function test_a_role_assigned_to_users_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Busy ' . uniqid(), 'guard_name' => 'web']);
        User::factory()->create(['is_active' => true])->assignRole($role);

        $this->actingAs($this->admin())->delete(route('admin.roles.destroy', $role))->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_an_unused_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'Temp ' . uniqid(), 'guard_name' => 'web']);

        $this->actingAs($this->admin())->delete(route('admin.roles.destroy', $role))->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
