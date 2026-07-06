<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use DatabaseTransactions;

    /** A web-listed product with $count active variants (first is default). */
    private function variableProduct(string $name, int $count = 2): Product
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => $name, 'slug' => 'var-' . uniqid(), 'sku' => 'VAR-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'variable',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ]);

        for ($i = 0; $i < $count; $i++) {
            ProductVariant::create([
                'product_id' => $product->id, 'sku' => 'VV-' . uniqid(),
                'retail_price' => 500 + ($i * 100), 'cost' => 100, 'stock_quantity' => 4,
                'is_default' => $i === 0, 'is_active' => true,
            ]);
        }

        return $product->refresh();
    }

    public function test_shop_shows_one_card_per_active_variant(): void
    {
        $product = $this->variableProduct('Multi Variant Gizmo', 3);

        $res = $this->get('/shop?q=' . urlencode('Multi Variant Gizmo'))->assertOk();

        // Three variants → three cards → three results.
        $res->assertSee('of 3 results');
        foreach ($product->variants as $variant) {
            $res->assertSee('variant=' . $variant->id, false);
        }
    }

    public function test_product_page_preselects_the_requested_variant(): void
    {
        $product = $this->variableProduct('Preselect Widget', 2);
        $second = $product->variants->last();

        $this->get('/product/' . $product->slug . '?variant=' . $second->id)
            ->assertOk()
            ->assertSee('productDetail(', false)      // reactive picker is present
            ->assertSee('initial: ' . $second->id, false); // pre-selected to the clicked variant
    }

    public function test_simple_product_still_shows_a_single_card(): void
    {
        $product = $this->variableProduct('Solo Item', 1);

        $this->get('/shop?q=' . urlencode('Solo Item'))->assertOk()->assertSee('of 1 results');
    }
}
