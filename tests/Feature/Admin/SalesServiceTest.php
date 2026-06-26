<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class SalesServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(float $retail, float $cost, float $stock, ?float $wholesale = null): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Goods ' . uniqid(), 'slug' => 'g-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true, 'is_stock_tracked' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V-' . uniqid(),
            'retail_price' => $retail, 'wholesale_price' => $wholesale, 'cost' => $cost, 'stock_quantity' => $stock,
            'is_default' => true, 'is_active' => true,
        ]);
    }

    private function service(): SalesService
    {
        return app(SalesService::class);
    }

    public function test_a_pos_sale_decrements_stock_and_posts_revenue_and_cogs(): void
    {
        $this->actingAs($this->admin());
        $v = $this->variant(retail: 100, cost: 60, stock: 10);

        $order = $this->service()->place('pos', null, [['variant' => $v, 'quantity' => 2]], ['payment_method' => 'cash', 'paid' => 200]);

        $this->assertSame('paid', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertSame('8.000', (string) $v->fresh()->stock_quantity);      // 10 − 2
        $this->assertSame('60.00', (string) $order->items()->first()->cost_snapshot);
        $this->assertSame(1, $order->payments()->count());

        // Ledger: Cash 200 (debit) = Sales revenue 200 (credit); COGS 120 (debit) = Inventory 120 (credit).
        $entries = LedgerEntry::where('reference_type', $order->getMorphClass())->where('reference_id', $order->id)->get();
        $this->assertEqualsWithDelta(320, $entries->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(320, $entries->sum('credit'), 0.01);
        $this->assertEqualsWithDelta(200, (float) $entries->firstWhere('account', 'sales_revenue')->credit, 0.01);
        $this->assertEqualsWithDelta(120, (float) $entries->firstWhere('account', 'cogs')->debit, 0.01);
    }

    public function test_an_unpaid_sale_records_a_receivable_and_no_payment(): void
    {
        $this->actingAs($this->admin());
        $v = $this->variant(retail: 50, cost: 30, stock: 10);

        $order = $this->service()->place('vendor', null, [['variant' => $v, 'quantity' => 4]], ['payment_method' => 'credit', 'paid' => 0]);

        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(0, $order->payments()->count());

        $entries = LedgerEntry::where('reference_type', $order->getMorphClass())->where('reference_id', $order->id)->get();
        $this->assertEqualsWithDelta(200, (float) $entries->firstWhere('account', 'accounts_receivable')->debit, 0.01);
    }

    public function test_vendor_channel_uses_the_wholesale_price(): void
    {
        $this->actingAs($this->admin());
        $v = $this->variant(retail: 100, cost: 60, stock: 10, wholesale: 80);

        $order = $this->service()->place('vendor', null, [['variant' => $v, 'quantity' => 1]], ['paid' => 0]);

        $this->assertSame('80.00', (string) $order->subtotal);   // wholesale, not retail
        $this->assertSame('wholesale', $order->price_tier);
    }

    public function test_insufficient_stock_is_rejected_and_rolled_back(): void
    {
        $this->actingAs($this->admin());
        $v = $this->variant(retail: 100, cost: 60, stock: 3);
        $before = Order::count();

        try {
            $this->service()->place('pos', null, [['variant' => $v, 'quantity' => 10]], ['paid' => 1000]);
            $this->fail('Expected the sale to be rejected.');
        } catch (RuntimeException $e) {
            // expected
        }

        $this->assertSame($before, Order::count());                 // no order persisted
        $this->assertSame('3.000', (string) $v->fresh()->stock_quantity); // stock untouched
    }
}
