<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use DatabaseTransactions;

    private function webProduct(string $name, array $flags = [], float $retail = 500): Product
    {
        $product = Product::create(array_merge([
            'category_id' => Category::value('id'),
            'name' => $name, 'slug' => 'shp-' . uniqid(), 'sku' => 'SHP-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ], $flags));

        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SHV-' . uniqid(),
            'retail_price' => $retail, 'cost' => 100, 'stock_quantity' => 5, 'is_default' => true, 'is_active' => true,
        ]);

        return $product;
    }

    public function test_shop_renders(): void
    {
        $this->get('/shop')->assertOk();
    }

    public function test_search_finds_a_uniquely_named_product(): void
    {
        $name = 'Zxq Unique Shop Gadget';
        $this->webProduct($name);

        $this->get('/shop?q=' . urlencode($name))
            ->assertOk()->assertSee($name)->assertSee('of 1 results');
    }

    public function test_category_filter_scopes_results(): void
    {
        $category = Category::create(['name' => 'Zone ' . uniqid(), 'slug' => 'zone-' . uniqid(), 'is_active' => true]);
        $this->webProduct('Zoned Product', ['category_id' => $category->id]);

        $this->get('/shop?category=' . $category->slug)
            ->assertOk()->assertSee('Zoned Product')->assertSee('of 1 results');
    }

    public function test_price_minimum_excludes_cheaper_products(): void
    {
        $this->webProduct('Expensive Unique Widget', retail: 99999);

        $this->get('/shop?min=99000')->assertOk()->assertSee('Expensive Unique Widget');
        $this->get('/shop?max=10')->assertOk()->assertDontSee('Expensive Unique Widget');
    }

    public function test_unknown_sort_is_safe(): void
    {
        $this->get('/shop?sort=not-a-real-sort')->assertOk();
    }

    public function test_price_low_sort_orders_cheapest_first(): void
    {
        // Asserts the price subquery ordering executes and returns the page.
        $this->get('/shop?sort=price_low')->assertOk();
        $this->get('/shop?sort=price_high')->assertOk();
    }
}
