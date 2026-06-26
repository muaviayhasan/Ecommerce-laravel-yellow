<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VendorSaleTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(float $retail = 100, float $wholesale = 80, float $stock = 10): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'V ' . uniqid(), 'slug' => 'v-' . uniqid(), 'sku' => 'V-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true, 'is_sellable' => true, 'is_stock_tracked' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'VV-' . uniqid(),
            'retail_price' => $retail, 'wholesale_price' => $wholesale, 'cost' => 40, 'stock_quantity' => $stock,
            'is_default' => true, 'is_active' => true,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create(['name' => 'Vendor ' . uniqid(), 'type' => 'vendor', 'price_tier' => 'wholesale', 'is_active' => true]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.vendor-sales.index'))->assertForbidden();
    }

    public function test_admin_can_open_the_screen(): void
    {
        $this->actingAs($this->admin())->get(route('admin.vendor-sales.index'))
            ->assertOk()->assertSee('Vendor sale');
    }

    public function test_search_returns_the_wholesale_price(): void
    {
        $v = $this->variant(retail: 100, wholesale: 80);

        $this->actingAs($this->admin())
            ->getJson(route('admin.vendor-sales.search', ['q' => $v->sku]))
            ->assertOk()
            ->assertJsonFragment(['sku' => $v->sku, 'price' => 80, 'retail' => 100]);
    }

    public function test_a_partial_payment_records_a_credit_order_with_a_receivable(): void
    {
        $v = $this->variant(retail: 100, wholesale: 80, stock: 10);
        $customer = $this->customer();

        $this->actingAs($this->admin())->post(route('admin.vendor-sales.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'credit',
            'paid' => '50',
            'items' => [['variant_id' => $v->id, 'quantity' => '2']],
        ])->assertRedirect(route('admin.vendor-sales.index'))->assertSessionHas('last_order_id');

        $order = Order::where('channel', 'vendor')->latest('id')->firstOrFail();
        $this->assertSame('wholesale', $order->price_tier);
        $this->assertSame('160.00', (string) $order->grand_total); // 80 × 2
        $this->assertSame('50.00', (string) $order->paid_total);
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame('8.000', (string) $v->fresh()->stock_quantity);
    }

    public function test_customer_is_required(): void
    {
        $v = $this->variant();

        $this->actingAs($this->admin())->post(route('admin.vendor-sales.store'), [
            'payment_method' => 'credit',
            'items' => [['variant_id' => $v->id, 'quantity' => '1']],
        ])->assertSessionHasErrors('customer_id');
    }
}
