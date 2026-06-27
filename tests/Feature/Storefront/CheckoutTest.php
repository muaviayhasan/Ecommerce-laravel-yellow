<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    private function variant(float $retail = 500, float $stock = 5): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Checkout Item ' . uniqid(), 'slug' => 'co-' . uniqid(), 'sku' => 'CO-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'COV-' . uniqid(),
            'retail_price' => $retail, 'cost' => 200, 'stock_quantity' => $stock, 'is_default' => true, 'is_active' => true,
        ]);
    }

    private function billing(array $override = []): array
    {
        return array_merge([
            'email' => 'buyer-' . uniqid() . '@example.test',
            'first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '03001234567',
            'line1' => '123 Main St', 'city' => 'Lahore', 'state' => 'Punjab', 'country' => 'Pakistan',
            'payment_method' => 'cod', 'terms' => '1',
        ], $override);
    }

    public function test_checkout_redirects_when_the_cart_is_empty(): void
    {
        $this->get(route('checkout'))->assertRedirect(route('cart'));
    }

    public function test_placing_an_order_creates_a_web_order_and_clears_the_cart(): void
    {
        $variant = $this->variant(retail: 500, stock: 5);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2]);

        $email = 'jane-' . uniqid() . '@example.test';
        $this->post(route('checkout.store'), $this->billing(['email' => $email]))
            ->assertRedirect(route('checkout.success'));

        $order = Order::where('channel', 'web')->latest('id')->firstOrFail();
        $this->assertSame('1000.00', (string) $order->subtotal);          // 2 × 500
        $this->assertSame('unpaid', $order->payment_status);              // COD → settled later
        $this->assertCount(1, $order->items);
        $this->assertCount(2, $order->addresses);                        // billing + shipping
        $this->assertSame('3.000', (string) $variant->fresh()->stock_quantity); // 5 − 2
        $this->assertDatabaseHas('customers', ['email' => $email]);

        // Cart is cleared after a successful order.
        $this->get(route('cart'))->assertSee('Your cart is empty');
    }

    public function test_validation_requires_contact_details_and_terms(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->post(route('checkout.store'), ['payment_method' => 'cod'])
            ->assertSessionHasErrors(['email', 'first_name', 'last_name', 'phone', 'line1', 'city', 'terms']);
    }

    public function test_success_page_shows_the_order(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);
        $this->post(route('checkout.store'), $this->billing());

        $order = Order::where('channel', 'web')->latest('id')->firstOrFail();
        $this->get(route('checkout.success'))->assertOk()->assertSee($order->order_number);
    }

    public function test_guest_cannot_reach_success_without_an_order(): void
    {
        $this->get(route('checkout.success'))->assertRedirect(route('home'));
    }
}
