<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function supplier(): Supplier
    {
        return Supplier::create(['name' => 'Supplier ' . uniqid(), 'opening_balance' => 0, 'is_active' => true]);
    }

    /** A purchasable product with one variant at a known cost + stock. */
    private function variant(float $cost, float $stock): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Widget ' . uniqid(), 'slug' => 'widget-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_purchasable' => true, 'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V-' . uniqid(),
            'cost' => $cost, 'retail_price' => $cost * 1.5, 'stock_quantity' => $stock,
            'is_default' => true, 'is_active' => true,
        ]);
    }

    private function draftPurchase(Supplier $supplier, ProductVariant $variant, float $qty, float $unitCost, float $paid = 0): Purchase
    {
        $this->actingAs($this->admin())->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'tax_total' => '0',
            'paid_total' => (string) $paid,
            'items' => [['product_variant_id' => $variant->id, 'quantity' => (string) $qty, 'unit_cost' => (string) $unitCost]],
        ])->assertRedirect();

        return Purchase::where('supplier_id', $supplier->id)->latest('id')->firstOrFail();
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.purchases.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_draft_purchase(): void
    {
        $variant = $this->variant(cost: 100, stock: 10);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 5, unitCost: 120, paid: 0);

        $this->assertSame('draft', $purchase->status);
        $this->assertSame('600.00', (string) $purchase->subtotal);   // 5 × 120
        $this->assertSame('600.00', (string) $purchase->grand_total);
        $this->assertDatabaseHas('purchase_items', ['purchase_id' => $purchase->id, 'product_variant_id' => $variant->id, 'line_total' => 600]);

        // A draft must NOT touch stock yet.
        $this->assertSame('10.000', (string) $variant->fresh()->stock_quantity);
    }

    public function test_receiving_updates_stock_moving_average_cost_and_ledger(): void
    {
        $variant = $this->variant(cost: 100, stock: 10);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 10, unitCost: 120, paid: 500);

        $this->actingAs($this->admin())->post(route('admin.purchases.receive', $purchase))->assertRedirect();

        $variant->refresh();
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame('20.000', (string) $variant->stock_quantity);        // 10 + 10
        $this->assertSame('110.00', (string) $variant->cost);                  // (10·100 + 10·120) / 20

        // One purchase_in movement, balance_after = 20.
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id, 'type' => 'purchase_in', 'quantity' => 10, 'balance_after' => 20,
        ]);

        // Ledger: inventory(debit 1200) = cash(500) + accounts_payable(700).
        $entries = LedgerEntry::where('reference_type', $purchase->getMorphClass())->where('reference_id', $purchase->id)->get();
        $this->assertEqualsWithDelta(1200, $entries->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(1200, $entries->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(700, (float) $entries->firstWhere('account', 'accounts_payable')->credit, 0.01);
    }

    public function test_a_purchase_cannot_be_received_twice(): void
    {
        $variant = $this->variant(cost: 50, stock: 0);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 4, unitCost: 50, paid: 0);

        $this->actingAs($this->admin())->post(route('admin.purchases.receive', $purchase))->assertRedirect();
        $this->actingAs($this->admin())->post(route('admin.purchases.receive', $purchase));

        // Still only +4 — the second receive was rejected by the service.
        $this->assertSame('4.000', (string) $variant->fresh()->stock_quantity);
        $this->assertSame(1, StockMovement::where('reference_id', $purchase->id)->where('reference_type', $purchase->getMorphClass())->count());
    }

    public function test_cancelling_a_received_purchase_reverses_stock(): void
    {
        $variant = $this->variant(cost: 100, stock: 10);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 6, unitCost: 100, paid: 0);

        $this->actingAs($this->admin())->post(route('admin.purchases.receive', $purchase))->assertRedirect();
        $this->assertSame('16.000', (string) $variant->fresh()->stock_quantity);

        $this->actingAs($this->admin())->post(route('admin.purchases.cancel', $purchase))->assertRedirect();

        $this->assertSame('cancelled', $purchase->fresh()->status);
        $this->assertSame('10.000', (string) $variant->fresh()->stock_quantity); // back to start
    }

    public function test_received_purchase_cannot_be_edited(): void
    {
        $variant = $this->variant(cost: 10, stock: 0);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 1, unitCost: 10, paid: 0);
        $this->actingAs($this->admin())->post(route('admin.purchases.receive', $purchase));

        $this->actingAs($this->admin())->get(route('admin.purchases.edit', $purchase))
            ->assertRedirect(route('admin.purchases.show', $purchase));
    }

    public function test_receiving_requires_the_receive_permission(): void
    {
        $variant = $this->variant(cost: 10, stock: 0);
        $purchase = $this->draftPurchase($this->supplier(), $variant, qty: 1, unitCost: 10, paid: 0);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.purchases.receive', $purchase))->assertForbidden();
    }
}
