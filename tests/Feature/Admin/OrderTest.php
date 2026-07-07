<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function makeOrder(array $attrs = []): Order
    {
        $customer = Customer::create(['name' => 'Test Buyer', 'type' => 'retail', 'price_tier' => 'retail', 'opening_balance' => 0, 'email' => 'buyer@x.test']);

        $order = Order::create(array_merge([
            'order_number' => 'ORD-' . uniqid(), 'channel' => 'web', 'customer_id' => $customer->id,
            'status' => 'pending', 'payment_method' => 'cod', 'payment_status' => 'unpaid',
            'subtotal' => 100, 'tax_total' => 0, 'shipping_total' => 0, 'grand_total' => 100, 'paid_total' => 0,
            'currency' => 'PKR', 'placed_at' => now(),
        ], $attrs));

        $order->items()->create([
            'product_variant_id' => ProductVariant::value('id'),
            'name_snapshot' => 'Test Product', 'sku_snapshot' => 'SKU-1',
            'unit_price' => 100, 'quantity' => 1, 'line_total' => 100, 'cost_snapshot' => 50,
        ]);
        $order->addresses()->create([
            'type' => 'shipping', 'name' => 'Test Buyer', 'line1' => '123 Mall Rd', 'city' => 'Lahore', 'country' => 'Pakistan',
        ]);

        return $order;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.orders.index'))
            ->assertOk()->assertSee('Orders');
    }

    public function test_admin_can_view_an_order_detail(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Test Product')
            ->assertSee('Test Buyer');
    }

    public function test_can_print_an_order_bill(): void
    {
        $order = $this->makeOrder();

        // Assert on content common to both bill formats (A4 + thermal) so the test
        // is independent of the ambient store bill_type setting.
        $this->actingAs($this->admin())
            ->get(route('admin.orders.print', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Test Product')
            ->assertSee('Subtotal');
    }

    public function test_printing_requires_view_permission(): void
    {
        $order = $this->makeOrder();

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.orders.print', $order))
            ->assertForbidden();
    }

    public function test_updating_status_records_history(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.status', $order), [
                'status' => 'shipped', 'note' => 'Handed to courier', 'courier' => 'TCS', 'tracking_number' => 'TR-123',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('TCS', $order->courier);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id, 'from_status' => 'pending', 'to_status' => 'shipped', 'note' => 'Handed to courier',
        ]);
    }

    public function test_delivered_sets_delivered_at(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertNotNull($order->refresh()->delivered_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.status', $order), ['status' => 'teleported'])
            ->assertSessionHasErrors('status');
    }

    public function test_updating_status_requires_edit_permission(): void
    {
        $order = $this->makeOrder();

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->patch(route('admin.orders.status', $order), ['status' => 'shipped'])
            ->assertForbidden();
    }
}
