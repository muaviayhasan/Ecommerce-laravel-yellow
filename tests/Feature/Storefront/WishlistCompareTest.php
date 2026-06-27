<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WishlistCompareTest extends TestCase
{
    use DatabaseTransactions;

    private function product(string $name): Product
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => $name, 'slug' => 'wc-' . uniqid(), 'sku' => 'WC-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'WCV-' . uniqid(),
            'retail_price' => 500, 'cost' => 200, 'stock_quantity' => 5, 'is_default' => true, 'is_active' => true,
        ]);

        return $product;
    }

    public function test_empty_wishlist_renders(): void
    {
        $this->get(route('wishlist'))->assertOk()->assertSee('wishlist is empty');
    }

    public function test_toggling_adds_then_removes_from_wishlist(): void
    {
        $product = $this->product('Ztest Wishlist Widget');

        $this->post(route('wishlist.toggle', $product->slug))->assertRedirect();
        $this->get(route('wishlist'))->assertOk()->assertSee('Ztest Wishlist Widget');

        $this->post(route('wishlist.toggle', $product->slug)); // toggle off
        $this->get(route('wishlist'))->assertSee('wishlist is empty');
    }

    public function test_compare_shows_products_and_caps_at_four(): void
    {
        $products = collect(range(1, 5))->map(fn ($i) => $this->product("Ztest Compare {$i} " . uniqid()));

        foreach ($products->take(4) as $product) {
            $this->post(route('compare.toggle', $product->slug))->assertRedirect();
        }

        // The fifth is rejected (limit 4).
        $this->post(route('compare.toggle', $products[4]->slug))->assertSessionHas('error');

        $response = $this->get(route('compare'))->assertOk();
        $response->assertSee($products[0]->name);
        $response->assertDontSee($products[4]->name);
    }

    public function test_removing_from_compare(): void
    {
        $product = $this->product('Ztest Removable Compare');
        $this->post(route('compare.toggle', $product->slug));

        $this->delete(route('compare.remove', $product->slug))->assertRedirect(route('compare'));
        $this->get(route('compare'))->assertSee('No products to compare');
    }
}
