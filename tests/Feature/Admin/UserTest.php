<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()->assertSee('Users')->assertSee('Add user');
    }

    public function test_storing_a_user_hashes_password_and_assigns_roles(): void
    {
        $email = 'cathy-' . uniqid() . '@x.test';

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Cathy Cashier', 'email' => $email, 'phone' => '0300-1234567',
                'password' => 'secret123', 'is_active' => '1', 'roles' => ['cashier'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('cashier'));
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
    }

    public function test_email_must_be_unique(): void
    {
        $existing = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), ['name' => 'Dup', 'email' => $existing->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_is_kept_on_blank_and_roles_sync(): void
    {
        $user = User::factory()->create();
        $user->assignRole('editor');
        $original = $user->password;

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Name', 'email' => $user->email, 'password' => '', 'is_active' => '1',
                'roles' => ['sales-rep'],
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame($original, $user->password, 'Blank password must keep the existing one.');
        $this->assertSame('Updated Name', $user->name);
        $this->assertTrue($user->hasRole('sales-rep'));
        $this->assertFalse($user->hasRole('editor'));
    }

    public function test_password_changes_when_provided(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name, 'email' => $user->email, 'password' => 'brand-new-pass', 'roles' => [],
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_deleting_a_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('editor');

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
