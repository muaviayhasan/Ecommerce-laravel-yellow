<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PosTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(string $name, float $retail = 100, float $stock = 10): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => $name, 'slug' => 'p-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true, 'is_sellable' => true, 'is_stock_tracked' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'POS-' . uniqid(),
            'retail_price' => $retail, 'cost' => round($retail * 0.6, 2), 'stock_quantity' => $stock,
            'is_default' => true, 'is_active' => true,
        ]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.pos.index'))->assertForbidden();
    }

    public function test_admin_can_open_the_pos(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pos.index'))
            ->assertOk()->assertSee('Point of Sale');
    }

    public function test_search_returns_matching_sellable_variants(): void
    {
        $v = $this->variant('Searchable Gadget');

        $this->actingAs($this->admin())
            ->getJson(route('admin.pos.search', ['q' => 'Searchable Gadget']))
            ->assertOk()
            ->assertJsonFragment(['sku' => $v->sku, 'name' => 'Searchable Gadget']);
    }

    public function test_completing_a_sale_places_a_pos_order_and_decrements_stock(): void
    {
        $v = $this->variant('Sold Item', retail: 100, stock: 10);

        $this->actingAs($this->admin())->post(route('admin.pos.store'), [
            'payment_method' => 'cash',
            'items' => [['variant_id' => $v->id, 'quantity' => '2']],
        ])->assertRedirect(route('admin.pos.index'))->assertSessionHas('last_order_id');

        $order = Order::where('channel', 'pos')->latest('id')->firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertSame('8.000', (string) $v->fresh()->stock_quantity);
    }

    public function test_an_empty_cart_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pos.store'), ['payment_method' => 'cash', 'items' => []])
            ->assertSessionHasErrors('items');
    }

    public function test_selling_requires_the_sell_permission(): void
    {
        $v = $this->variant('Guarded Item');

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.pos.store'), ['payment_method' => 'cash', 'items' => [['variant_id' => $v->id, 'quantity' => '1']]])
            ->assertForbidden();
    }
}
