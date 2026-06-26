<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use DatabaseTransactions;

    private function webProduct(array $attrs = []): Product
    {
        $product = Product::create(array_merge([
            'category_id' => Category::value('id'),
            'name' => 'Detail Phone ' . uniqid(), 'slug' => 'detail-' . uniqid(), 'sku' => 'DP-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
            'short_description' => 'A great phone.', 'description' => 'Full description here.',
            'highlights' => ['Fast charging', 'OLED display'], 'specifications' => ['General' => ['Brand' => 'Acme']],
        ], $attrs));

        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'DPV-' . uniqid(),
            'retail_price' => 700, 'compare_at_price' => 900, 'cost' => 400,
            'stock_quantity' => 8, 'is_default' => true, 'is_active' => true,
        ]);

        return $product;
    }

    public function test_detail_page_renders_real_product(): void
    {
        $product = $this->webProduct(['name' => 'Nebula X Phone']);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Nebula X Phone')
            ->assertSee('Fast charging')      // highlight
            ->assertSee('Acme')               // spec value
            ->assertSee('Full description here.');
    }

    public function test_unknown_slug_404s(): void
    {
        $this->get(route('product.show', 'no-such-product-' . uniqid()))->assertNotFound();
    }

    public function test_unpublished_product_404s(): void
    {
        $product = $this->webProduct(['published_at' => null]);

        $this->get(route('product.show', $product->slug))->assertNotFound();
    }

    public function test_only_approved_reviews_are_shown(): void
    {
        $product = $this->webProduct();
        $product->reviews()->create([
            'user_id' => User::factory()->create()->id, 'rating' => 5, 'title' => 'Loved it',
            'body' => 'Approved review body here', 'is_approved' => true,
        ]);
        $product->reviews()->create([
            'user_id' => User::factory()->create()->id, 'rating' => 1,
            'body' => 'Hidden pending review text', 'is_approved' => false,
        ]);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Approved review body here')
            ->assertDontSee('Hidden pending review text');
    }
}
