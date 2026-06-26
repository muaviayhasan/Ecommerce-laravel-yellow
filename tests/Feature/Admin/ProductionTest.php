<?php

namespace Tests\Feature\Admin;

use App\Models\Bom;
use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(string $type, float $cost, float $stock): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => ucfirst($type) . ' ' . uniqid(), 'slug' => $type . '-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => $type === 'finished' ? 'manufactured' : 'raw', 'variant_mode' => 'simple', 'is_active' => true, 'is_stock_tracked' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V-' . uniqid(),
            'cost' => $cost, 'retail_price' => 0, 'stock_quantity' => $stock, 'is_default' => true, 'is_active' => true,
        ]);
    }

    /** @param array<int, array{0: ProductVariant, 1: float, 2: float}> $components [variant, qty, waste%] */
    private function bom(ProductVariant $finished, array $components, float $output = 1, float $labor = 0, float $overhead = 0): Bom
    {
        $bom = Bom::create([
            'product_id' => $finished->product_id, 'product_variant_id' => $finished->id,
            'output_quantity' => $output, 'labor_cost' => $labor, 'overhead_cost' => $overhead, 'is_active' => true,
        ]);
        foreach ($components as [$variant, $qty, $waste]) {
            $bom->items()->create(['component_variant_id' => $variant->id, 'quantity' => $qty, 'waste_percent' => $waste]);
        }

        return $bom;
    }

    private function draftOrder(Bom $bom, float $qty): ProductionOrder
    {
        $this->actingAs($this->admin())->post(route('admin.production.store'), ['bom_id' => $bom->id, 'quantity' => (string) $qty])->assertRedirect();

        return ProductionOrder::latest('id')->firstOrFail();
    }

    public function test_completing_a_run_consumes_components_produces_finished_and_posts_the_ledger(): void
    {
        $compA = $this->variant('raw', cost: 10, stock: 100);
        $compB = $this->variant('raw', cost: 5, stock: 100);
        $finished = $this->variant('finished', cost: 0, stock: 0);
        $bom = $this->bom($finished, [[$compA, 2, 0], [$compB, 3, 0]], output: 1, labor: 20, overhead: 10);

        $order = $this->draftOrder($bom, 10);
        $this->actingAs($this->admin())->post(route('admin.production.complete', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->status);

        // Components consumed: A 2×10=20 (100→80), B 3×10=30 (100→70).
        $this->assertSame('80.000', (string) $compA->fresh()->stock_quantity);
        $this->assertSame('70.000', (string) $compB->fresh()->stock_quantity);

        // Finished produced: 10 units at unit cost 65 = (2·10 + 3·5) + labor 20 + overhead 10.
        $this->assertSame('10.000', (string) $finished->fresh()->stock_quantity);
        $this->assertSame('65.00', (string) $finished->fresh()->cost);
        $this->assertSame('65.00', (string) $order->unit_cost);
        $this->assertSame('350.00', (string) $order->total_component_cost);
        $this->assertSame(2, $order->consumptions()->count());

        // Ledger: Finished Inventory(debit 650) = Raw(350) + Labor(200) + Overhead(100).
        $entries = LedgerEntry::where('reference_type', $order->getMorphClass())->where('reference_id', $order->id)->get();
        $this->assertEqualsWithDelta(650, $entries->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(650, $entries->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(650, (float) $entries->firstWhere('account', 'inventory_finished')->debit, 0.01);
    }

    public function test_completing_with_insufficient_components_is_rejected_and_rolled_back(): void
    {
        $comp = $this->variant('raw', cost: 10, stock: 1);     // only 1 on hand
        $finished = $this->variant('finished', cost: 0, stock: 0);
        $bom = $this->bom($finished, [[$comp, 5, 0]], output: 1); // needs 5

        $order = $this->draftOrder($bom, 1);
        $this->actingAs($this->admin())->post(route('admin.production.complete', $order))->assertRedirect();

        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame('1.000', (string) $comp->fresh()->stock_quantity);   // untouched
        $this->assertSame('0.000', (string) $finished->fresh()->stock_quantity);
    }

    public function test_cancelling_a_completed_run_reverses_stock(): void
    {
        $comp = $this->variant('raw', cost: 10, stock: 50);
        $finished = $this->variant('finished', cost: 0, stock: 0);
        $bom = $this->bom($finished, [[$comp, 4, 0]], output: 1);

        $order = $this->draftOrder($bom, 5);
        $this->actingAs($this->admin())->post(route('admin.production.complete', $order))->assertRedirect();
        $this->assertSame('30.000', (string) $comp->fresh()->stock_quantity); // 50 − 20
        $this->assertSame('5.000', (string) $finished->fresh()->stock_quantity);

        $this->actingAs($this->admin())->post(route('admin.production.cancel', $order))->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('50.000', (string) $comp->fresh()->stock_quantity);  // returned
        $this->assertSame('0.000', (string) $finished->fresh()->stock_quantity); // removed
    }

    public function test_completing_requires_the_complete_permission(): void
    {
        $comp = $this->variant('raw', cost: 10, stock: 50);
        $finished = $this->variant('finished', cost: 0, stock: 0);
        $order = $this->draftOrder($this->bom($finished, [[$comp, 1, 0]]), 1);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.production.complete', $order))->assertForbidden();
    }
}
