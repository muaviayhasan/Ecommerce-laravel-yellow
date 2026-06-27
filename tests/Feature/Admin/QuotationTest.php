<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function variant(float $retail = 100, float $stock = 10): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Q ' . uniqid(), 'slug' => 'q-' . uniqid(), 'sku' => 'Q-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true, 'is_sellable' => true, 'is_stock_tracked' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'QV-' . uniqid(),
            'retail_price' => $retail, 'cost' => round($retail * 0.5, 2), 'stock_quantity' => $stock,
            'is_default' => true, 'is_active' => true,
        ]);
    }

    private function makeQuotation(string $status, ProductVariant $v, float $qty = 2, float $price = 100): Quotation
    {
        $q = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'status' => $status, 'price_tier' => 'retail',
            'subtotal' => $qty * $price, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => $qty * $price,
            'created_by' => $this->admin()->id,
        ]);
        $q->items()->create([
            'product_variant_id' => $v->id, 'name_snapshot' => 'Q', 'quantity' => $qty, 'unit_price' => $price, 'line_total' => $qty * $price,
        ]);

        return $q;
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.quotations.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_quotation_and_totals_are_computed(): void
    {
        $v = $this->variant();

        $this->actingAs($this->admin())->post(route('admin.quotations.store'), [
            'price_tier' => 'retail',
            'discount_type' => 'fixed',
            'discount_value' => '10',
            'tax_total' => '0',
            'items' => [['product_variant_id' => $v->id, 'quantity' => '2', 'unit_price' => '100']],
        ])->assertRedirect();

        $q = Quotation::latest('id')->firstOrFail();
        $this->assertSame('draft', $q->status);
        $this->assertSame('200.00', (string) $q->subtotal);
        $this->assertSame('10.00', (string) $q->discount_total);
        $this->assertSame('190.00', (string) $q->grand_total); // 200 - 10 discount
        $this->assertCount(1, $q->items);
    }

    public function test_status_can_be_advanced(): void
    {
        $q = $this->makeQuotation('sent', $this->variant());

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.status', $q), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertSame('accepted', $q->fresh()->status);
    }

    public function test_accepted_quotation_converts_to_an_order_and_moves_stock(): void
    {
        $v = $this->variant(retail: 100, stock: 10);
        $q = $this->makeQuotation('accepted', $v, qty: 2, price: 100);

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.convert', $q))
            ->assertRedirect();

        $q->refresh();
        $this->assertSame('converted', $q->status);
        $this->assertNotNull($q->converted_order_id);
        $this->assertSame('8.000', (string) $v->fresh()->stock_quantity);

        $order = Order::findOrFail($q->converted_order_id);
        $this->assertSame($q->id, $order->quotation_id);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertSame('unpaid', $order->payment_status); // credit conversion
    }

    public function test_only_accepted_quotations_can_be_converted(): void
    {
        $q = $this->makeQuotation('sent', $this->variant());

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.convert', $q))
            ->assertRedirect();

        $this->assertSame('sent', $q->fresh()->status); // unchanged
    }
}
