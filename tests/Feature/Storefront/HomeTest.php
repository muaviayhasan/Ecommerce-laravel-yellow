<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Storefront;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use DatabaseTransactions;

    private function webProduct(string $name, array $flags = [], float $retail = 500, ?float $compare = null): Product
    {
        $product = Product::create(array_merge([
            'category_id' => Category::value('id'),
            'name' => $name, 'slug' => 'sp-' . uniqid(), 'sku' => 'SP-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ], $flags));

        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SPV-' . uniqid(),
            'retail_price' => $retail, 'compare_at_price' => $compare, 'cost' => 300,
            'stock_quantity' => 5, 'is_default' => true, 'is_active' => true,
        ]);

        return $product;
    }

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_query_includes_a_web_listed_product(): void
    {
        $product = $this->webProduct('Included Phone');

        $this->assertTrue(Storefront::query()->pluck('id')->contains($product->id));
    }

    public function test_query_excludes_unpublished_and_unlisted_products(): void
    {
        $draft = $this->webProduct('Draft Phone', ['published_at' => null]);
        $unlisted = $this->webProduct('Unlisted Phone', ['is_web_listed' => false]);

        $ids = Storefront::query()->pluck('id');
        $this->assertFalse($ids->contains($draft->id));
        $this->assertFalse($ids->contains($unlisted->id));
    }

    public function test_card_maps_to_the_expected_shape(): void
    {
        $product = $this->webProduct('Shape Test Phone', retail: 500, compare: 650)
            ->fresh()->load('defaultVariant.image', 'category', 'media');

        $card = Storefront::card($product);

        $this->assertSame('Shape Test Phone', $card['name']);
        $this->assertSame(500.0, $card['price']);
        $this->assertSame(650.0, $card['compare']); // compare > retail → on sale
        $this->assertStringContainsString($product->slug, $card['url']);
        $this->assertNotEmpty($card['image']);
    }

    public function test_compare_below_retail_is_not_treated_as_a_sale(): void
    {
        $product = $this->webProduct('No Sale Phone', retail: 500, compare: 400)
            ->fresh()->load('defaultVariant.image', 'category', 'media');

        $this->assertNull(Storefront::card($product)['compare']);
    }
}
