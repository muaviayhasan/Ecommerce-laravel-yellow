<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    private function variant(float $retail = 500, float $stock = 5, bool $sellable = true): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Cart Item ' . uniqid(), 'slug' => 'cart-' . uniqid(), 'sku' => 'CT-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => $sellable, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'CTV-' . uniqid(),
            'retail_price' => $retail, 'cost' => 200, 'stock_quantity' => $stock, 'is_default' => true, 'is_active' => true,
        ]);
    }

    public function test_empty_cart_page_renders(): void
    {
        $this->get(route('cart'))->assertOk()->assertSee('Your cart is empty');
    }

    public function test_add_to_cart_then_view(): void
    {
        $variant = $this->variant(retail: 500);

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])->assertRedirect();

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee($variant->product->name)
            ->assertSee('Rs 1,000'); // 2 × 500 line total
    }

    public function test_updating_quantity_changes_the_total(): void
    {
        $variant = $this->variant(retail: 500);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->patch(route('cart.update', $variant->id), ['quantity' => 3])->assertRedirect(route('cart'));

        $this->get(route('cart'))->assertSee('Rs 1,500'); // 3 × 500
    }

    public function test_removing_an_item_empties_the_cart(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id]);

        $this->delete(route('cart.remove', $variant->id))->assertRedirect(route('cart'));

        $this->get(route('cart'))->assertSee('Your cart is empty');
    }

    public function test_non_sellable_product_cannot_be_added(): void
    {
        $variant = $this->variant(sellable: false);

        $this->post(route('cart.add'), ['variant_id' => $variant->id])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
