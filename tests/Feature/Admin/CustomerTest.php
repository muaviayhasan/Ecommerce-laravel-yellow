<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.customers.index'))->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.customers.index'))
            ->assertOk()->assertSee('Customers')->assertSee('Add customer');
    }

    public function test_storing_a_customer(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.customers.store'), [
                'name' => 'Acme Trading', 'type' => 'wholesale', 'price_tier' => 'wholesale',
                'opening_balance' => 1500, 'is_active' => '1', 'email' => 'acme@x.test',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', ['name' => 'Acme Trading', 'type' => 'wholesale', 'is_active' => true]);
    }

    public function test_updating_a_customer(): void
    {
        $customer = Customer::create(['name' => 'Old', 'type' => 'retail', 'price_tier' => 'retail', 'opening_balance' => 0]);

        $this->actingAs($this->admin())
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Renamed', 'type' => 'retail', 'price_tier' => 'retail', 'opening_balance' => 250,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Renamed']);
    }

    public function test_deleting_a_customer(): void
    {
        $customer = Customer::create(['name' => 'Gone', 'type' => 'retail', 'price_tier' => 'retail', 'opening_balance' => 0]);

        $this->actingAs($this->admin())
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_cannot_delete_a_customer_with_orders(): void
    {
        $customer = Customer::create(['name' => 'Has orders', 'type' => 'retail', 'price_tier' => 'retail', 'opening_balance' => 0]);
        Order::create(['order_number' => 'ORD-' . uniqid(), 'customer_id' => $customer->id, 'channel' => 'web', 'status' => 'pending', 'payment_status' => 'unpaid']);

        $this->actingAs($this->admin())
            ->delete(route('admin.customers.destroy', $customer))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
