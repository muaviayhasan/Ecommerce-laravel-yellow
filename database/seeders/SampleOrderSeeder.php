<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Demo orders for showcasing the admin Orders module. Run on demand:
 *   php artisan db:seed --class=SampleOrderSeeder
 * Idempotent on order_number, so re-running won't duplicate.
 */
class SampleOrderSeeder extends Seeder
{
    public function run(): void
    {
        $ayesha = Customer::firstOrCreate(['name' => 'Ayesha Khan'], [
            'type' => 'retail', 'price_tier' => 'retail', 'email' => 'ayesha@example.test',
            'phone' => '0301-1234567', 'opening_balance' => 0, 'is_active' => true,
        ]);
        $bilal = Customer::firstOrCreate(['name' => 'Bilal Traders'], [
            'type' => 'wholesale', 'price_tier' => 'wholesale', 'email' => 'bilal@example.test',
            'phone' => '0302-7654321', 'opening_balance' => 5000, 'is_active' => true,
        ]);

        $variants = ProductVariant::with('product')->take(5)->get();

        if ($variants->isEmpty()) {
            $this->command?->warn('No product variants found — run CatalogSeeder first.');

            return;
        }

        $this->order('10001', $ayesha, $variants->take(3), 'shipped', 'paid', 'card', $variants);
        $this->order('10002', $bilal, $variants->slice(1, 2), 'processing', 'partial', 'bank', $variants);
        $this->order('10003', $ayesha, $variants->take(1), 'delivered', 'paid', 'cod', $variants);
        $this->order('10004', $bilal, $variants->take(2), 'pending', 'unpaid', 'cod', $variants);

        $this->command?->info('Sample orders seeded: ' . Order::count() . ' total.');
    }

    private function order(string $number, Customer $customer, $lineVariants, string $status, string $payment, string $method): Order
    {
        if ($existing = Order::where('order_number', $number)->first()) {
            return $existing;
        }

        $subtotal = 0;
        $rows = [];
        foreach ($lineVariants->values() as $i => $variant) {
            $qty = $i + 1;
            $price = (float) $variant->retail_price;
            $line = $price * $qty;
            $subtotal += $line;
            $rows[] = [
                'product_variant_id' => $variant->id,
                'name_snapshot' => $variant->product->name,
                'sku_snapshot' => $variant->sku ?: 'SKU-' . $variant->id,
                'unit_price' => $price, 'quantity' => $qty, 'line_total' => $line, 'cost_snapshot' => (float) $variant->cost,
            ];
        }

        $shipping = 200;
        $grand = $subtotal + $shipping;
        $paid = match ($payment) {
            'paid' => $grand,
            'partial' => round($grand / 2, 2),
            default => 0,
        };

        $order = Order::create([
            'order_number' => $number, 'channel' => 'web', 'customer_id' => $customer->id,
            'status' => $status, 'payment_method' => $method, 'payment_status' => $payment,
            'subtotal' => $subtotal, 'tax_total' => 0, 'shipping_total' => $shipping,
            'grand_total' => $grand, 'paid_total' => $paid, 'currency' => 'PKR', 'placed_at' => now(),
        ]);

        foreach ($rows as $row) {
            $order->items()->create($row);
        }

        foreach (['shipping', 'billing'] as $type) {
            $order->addresses()->create([
                'type' => $type, 'name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email,
                'line1' => 'House 12, Block C, Gulberg III', 'city' => 'Lahore', 'state' => 'Punjab', 'zip' => '54000', 'country' => 'Pakistan',
            ]);
        }

        $order->payments()->create(['gateway' => $method, 'amount' => $paid, 'status' => $paid > 0 ? 'succeeded' : 'pending']);

        $order->statusHistory()->create(['from_status' => null, 'to_status' => 'pending', 'note' => 'Order placed']);
        if ($status !== 'pending') {
            $order->statusHistory()->create(['from_status' => 'pending', 'to_status' => $status]);
        }

        return $order;
    }
}
