<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Widget ' . uniqid(),
            'category_id' => Category::value('id'),
            'brand_id' => Brand::value('id'),
            'type' => 'trading',
            'variant_mode' => 'simple',
            'is_active' => '1',
            'is_web_listed' => '1',
            'published' => '1',
            'variant' => ['retail_price' => '199.99', 'cost' => '120', 'stock_quantity' => '10', 'low_stock_threshold' => '2'],
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.products.index'))
            ->assertOk()->assertSee('Products');
    }

    public function test_admin_can_view_a_product_detail(): void
    {
        $product = Product::firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.products.show', $product))
            ->assertOk()->assertSee($product->name)->assertSee('Storefront placement');
    }

    public function test_admin_can_create_a_product_with_a_default_variant(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['name' => 'Brand New Gizmo']))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Brand New Gizmo')->firstOrFail();
        $this->assertNotNull($product->published_at);
        $this->assertTrue($product->is_active);

        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);
        $this->assertSame('199.99', (string) $variant->retail_price);
        $this->assertTrue($variant->is_default);
    }

    public function test_retail_price_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['variant' => ['retail_price' => '']]))
            ->assertSessionHasErrors('variant.retail_price');
    }

    public function test_slug_and_sku_auto_generate_from_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['name' => 'Auto Slug Product', 'slug' => '', 'sku' => '']))
            ->assertRedirect();

        $product = Product::where('name', 'Auto Slug Product')->firstOrFail();
        $this->assertSame('auto-slug-product', $product->slug);
        $this->assertNotEmpty($product->sku);
    }

    public function test_storefront_flags_persist(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload([
                'name' => 'Flagged Product', 'is_featured' => '1', 'is_trending' => '1', 'is_bestseller' => '1',
            ]))
            ->assertRedirect();

        $product = Product::where('name', 'Flagged Product')->firstOrFail();
        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->is_trending);
        $this->assertTrue($product->is_bestseller);
    }

    public function test_creating_attaches_images_with_primary(): void
    {
        $media = Media::create(['disk' => 'public', 'path' => 'test/' . uniqid() . '.jpg', 'mime' => 'image/jpeg', 'size' => 1024]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['name' => 'Imaged Product', 'images' => [$media->id]]))
            ->assertRedirect();

        $product = Product::where('name', 'Imaged Product')->firstOrFail();
        $this->assertTrue($product->media->contains($media->id));
        $this->assertEquals(1, $product->media()->wherePivot('is_primary', true)->count());
    }

    public function test_admin_can_update_a_product(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload(['name' => 'Before Edit']));
        $product = Product::where('name', 'Before Edit')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'name' => 'After Edit', 'slug' => $product->slug, 'sku' => $product->sku,
                'variant' => ['retail_price' => '250'],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('After Edit', $product->name);
        $this->assertSame('250.00', (string) $product->defaultVariant->retail_price);
    }

    public function test_creating_requires_create_permission(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.products.store'), $this->payload())
            ->assertForbidden();
    }

    /**
     * Create a fresh variation attribute with $take values and return their ids.
     * Self-contained so the test doesn't depend on seeded attribute data.
     *
     * @return array<int,int>
     */
    private function values(string $name, int $take): array
    {
        $attribute = Attribute::create([
            'name' => ucfirst($name) . ' ' . uniqid(),
            'code' => $name . '-' . uniqid(),
            'type' => 'select', 'is_variation' => true, 'sort_order' => 0,
        ]);

        return collect(range(1, $take))
            ->map(fn ($i) => $attribute->values()->create([
                'value' => $name . $i, 'label' => ucfirst($name) . ' ' . $i, 'sort_order' => $i,
            ])->id)
            ->all();
    }

    private function variablePayload(array $colors, array $sizes, array $overrides = []): array
    {
        $variants = [];
        $i = 0;
        foreach ($colors as $c) {
            foreach ($sizes as $s) {
                $variants[] = ['value_ids' => [$c, $s], 'retail_price' => (string) (100 + $i * 10), 'stock_quantity' => '5'];
                $i++;
            }
        }

        return array_merge([
            'name' => 'iPhone Variant ' . uniqid(),
            'category_id' => Category::value('id'),
            'type' => 'trading',
            'variant_mode' => 'variable',
            'is_active' => '1',
            'is_web_listed' => '1',
            'published' => '1',
            'variant_default' => '0',
            'variants' => $variants,
        ], $overrides);
    }

    public function test_admin_can_create_a_variable_product_with_a_matrix(): void
    {
        $colors = $this->values('color', 2);
        $sizes = $this->values('size', 2);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->variablePayload($colors, $sizes, ['name' => 'Matrix Phone']))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Matrix Phone')->firstOrFail();
        $this->assertSame('variable', $product->variant_mode);
        $this->assertSame(4, $product->variants()->count());                 // 2 colours × 2 sizes
        $this->assertSame(1, $product->variants()->where('is_default', true)->count());
        $this->assertSame(2, $product->attributes()->count());               // colour + size linked

        // Each variant carries its 2 attribute values.
        $this->assertSame(2, $product->variants()->first()->attributeValues()->count());
    }

    public function test_variable_product_requires_at_least_one_variant(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->variablePayload([], [], ['variants' => []]))
            ->assertSessionHasErrors('variants');
    }

    public function test_default_variant_is_chosen_by_index(): void
    {
        $colors = $this->values('color', 2);
        $sizes = $this->values('size', 2);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->variablePayload($colors, $sizes, ['name' => 'Default Idx', 'variant_default' => '2']));

        $product = Product::where('name', 'Default Idx')->firstOrFail();
        $default = $product->variants()->where('is_default', true)->firstOrFail();
        // The 3rd generated row (index 2) is colour[1]+size[0].
        $this->assertEqualsCanonicalizing([$colors[1], $sizes[0]], $default->attributeValues->pluck('id')->all());
    }

    public function test_switching_variable_to_simple_collapses_variants(): void
    {
        $colors = $this->values('color', 2);
        $sizes = $this->values('size', 2);
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->variablePayload($colors, $sizes, ['name' => 'Will Collapse']));
        $product = Product::where('name', 'Will Collapse')->firstOrFail();
        $this->assertSame(4, $product->variants()->count());

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'name' => 'Will Collapse', 'slug' => $product->slug, 'sku' => $product->sku,
                'variant_mode' => 'simple', 'variant' => ['retail_price' => '499'],
            ]))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('simple', $product->variant_mode);
        $this->assertSame(1, $product->variants()->count());
        $this->assertSame(0, $product->attributes()->count());
        $this->assertSame('499.00', (string) $product->defaultVariant->retail_price);
    }

    public function test_specifications_highlights_and_details_persist(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload([
            'name' => 'Detailed Product',
            'warranty' => '1 Year Manufacturer Warranty',
            'return_policy' => '7-day returns accepted.',
            'video_url' => 'https://youtube.com/watch?v=abc',
            'highlights' => ['144 Hz display', '', '16 GB RAM'],   // blank dropped
            'specs' => [
                ['group' => 'Display', 'label' => 'Screen', 'value' => '17.3 in'],
                ['group' => 'Display', 'label' => 'Refresh', 'value' => '144 Hz'],
                ['group' => 'Performance', 'label' => 'CPU', 'value' => 'Core i7'],
                ['group' => '', 'label' => '', 'value' => 'no label → dropped'],
            ],
        ]))->assertRedirect();

        $product = Product::where('name', 'Detailed Product')->firstOrFail();
        $this->assertSame('1 Year Manufacturer Warranty', $product->warranty);
        $this->assertSame('7-day returns accepted.', $product->return_policy);
        $this->assertSame(['144 Hz display', '16 GB RAM'], $product->highlights);
        $this->assertSame([
            'Display' => ['Screen' => '17.3 in', 'Refresh' => '144 Hz'],
            'Performance' => ['CPU' => 'Core i7'],
        ], $product->specifications);
    }

    public function test_invalid_video_url_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['video_url' => 'not-a-url']))
            ->assertSessionHasErrors('video_url');
    }

    public function test_destroy_soft_deletes(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload(['name' => 'To Delete']));
        $product = Product::where('name', 'To Delete')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
