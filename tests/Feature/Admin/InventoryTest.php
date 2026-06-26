<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(float $cost, float $stock): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Stocked ' . uniqid(), 'slug' => 'stocked-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_stock_tracked' => true, 'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V-' . uniqid(),
            'cost' => $cost, 'retail_price' => $cost * 2, 'stock_quantity' => $stock,
            'low_stock_threshold' => 5, 'is_default' => true, 'is_active' => true,
        ]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.inventory.index'))->assertForbidden();
    }

    public function test_admin_can_view_inventory(): void
    {
        $this->actingAs($this->admin())->get(route('admin.inventory.index'))
            ->assertOk()->assertSee('Inventory');
    }

    public function test_adding_stock_records_a_movement_and_a_ledger_gain(): void
    {
        $variant = $this->variant(cost: 100, stock: 10);

        $this->actingAs($this->admin())
            ->post(route('admin.inventory.adjust', $variant), ['mode' => 'add', 'quantity' => '5', 'reason' => 'Found extra'])
            ->assertRedirect();

        $variant->refresh();
        $this->assertSame('15.000', (string) $variant->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'adjustment', 'quantity' => 5, 'balance_after' => 15]);

        $movement = StockMovement::where('product_variant_id', $variant->id)->latest('id')->firstOrFail();
        $entries = LedgerEntry::where('reference_type', $movement->getMorphClass())->where('reference_id', $movement->id)->get();
        $this->assertEqualsWithDelta(500, $entries->sum('debit'), 0.01);   // 5 × 100
        $this->assertEqualsWithDelta(500, $entries->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(500, (float) $entries->firstWhere('account', 'inventory')->debit, 0.01); // gain → inventory up
    }

    public function test_setting_a_lower_count_writes_off_stock(): void
    {
        $variant = $this->variant(cost: 100, stock: 10);

        $this->actingAs($this->admin())
            ->post(route('admin.inventory.adjust', $variant), ['mode' => 'set', 'quantity' => '8', 'reason' => 'Stock count'])
            ->assertRedirect();

        $this->assertSame('8.000', (string) $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'quantity' => -2, 'balance_after' => 8]);

        $movement = StockMovement::where('product_variant_id', $variant->id)->latest('id')->firstOrFail();
        $writeOff = LedgerEntry::where('reference_id', $movement->id)->where('reference_type', $movement->getMorphClass())->firstWhere('account', 'inventory_adjustment');
        $this->assertEqualsWithDelta(200, (float) $writeOff->debit, 0.01); // 2 × 100 expensed
    }

    public function test_negative_stock_is_rejected(): void
    {
        $variant = $this->variant(cost: 50, stock: 3);

        $this->actingAs($this->admin())
            ->post(route('admin.inventory.adjust', $variant), ['mode' => 'add', 'quantity' => '-5', 'reason' => 'Damage'])
            ->assertRedirect();

        // Rolled back: stock unchanged, no movement written.
        $this->assertSame('3.000', (string) $variant->fresh()->stock_quantity);
        $this->assertDatabaseMissing('stock_movements', ['product_variant_id' => $variant->id]);
    }

    public function test_a_reason_is_required(): void
    {
        $variant = $this->variant(cost: 10, stock: 10);

        $this->actingAs($this->admin())
            ->post(route('admin.inventory.adjust', $variant), ['mode' => 'add', 'quantity' => '1', 'reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_adjusting_requires_the_adjust_permission(): void
    {
        $variant = $this->variant(cost: 10, stock: 10);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.inventory.adjust', $variant), ['mode' => 'add', 'quantity' => '1', 'reason' => 'x'])
            ->assertForbidden();
    }
}
