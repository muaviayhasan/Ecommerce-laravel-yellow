<?php

namespace Tests\Feature\Admin;

use App\Models\Bom;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BomTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function product(): Product
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Kit ' . uniqid(), 'slug' => 'kit-' . uniqid(), 'sku' => 'K-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'KV-' . uniqid(), 'cost' => 0, 'retail_price' => 0, 'is_default' => true, 'is_active' => true]);

        return $product;
    }

    private function componentVariant(float $cost): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Comp ' . uniqid(), 'slug' => 'comp-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'raw', 'variant_mode' => 'simple', 'is_active' => true,
        ]);

        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'CV-' . uniqid(), 'cost' => $cost, 'retail_price' => 0, 'is_default' => true, 'is_active' => true]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.boms.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_bom_and_the_product_becomes_manufacturable(): void
    {
        $product = $this->product();
        $compA = $this->componentVariant(10);

        $this->actingAs($this->admin())->post(route('admin.boms.store'), [
            'product_id' => $product->id,
            'output_quantity' => '1', 'labor_cost' => '20', 'overhead_cost' => '10', 'is_active' => '1',
            'items' => [['component_variant_id' => $compA->id, 'quantity' => '2', 'waste_percent' => '0']],
        ])->assertRedirect();

        $bom = Bom::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(1, $bom->items()->count());
        $this->assertTrue($product->fresh()->is_manufacturable);
        $this->assertSame($product->defaultVariant->id, $bom->product_variant_id);
    }

    public function test_bom_unit_cost_accounts_for_waste_labor_and_overhead(): void
    {
        // 2 × cost 10 × (1 + 50% waste) = 30; + labor 20 + overhead 10 = 60; ÷ output 2 = 30/unit.
        $product = $this->product();
        $compA = $this->componentVariant(10);

        $this->actingAs($this->admin())->post(route('admin.boms.store'), [
            'product_id' => $product->id,
            'output_quantity' => '2', 'labor_cost' => '20', 'overhead_cost' => '10', 'is_active' => '1',
            'items' => [['component_variant_id' => $compA->id, 'quantity' => '2', 'waste_percent' => '50']],
        ])->assertRedirect();

        $bom = Bom::where('product_id', $product->id)->firstOrFail();
        $this->assertEqualsWithDelta(30.0, app(\App\Services\BomService::class)->unitCost($bom), 0.01);
    }
}
