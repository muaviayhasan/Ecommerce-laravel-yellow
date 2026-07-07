<?php

namespace Tests\Feature\Storefront;

use App\Mail\Admin\ContactMessageMail;
use App\Mail\Admin\NewQuoteRequestMail;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use DatabaseTransactions;

    private function webVariant(string $name = 'Quote Widget'): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::query()->value('id'),
            'name' => $name,
            'slug' => 'q-' . Str::lower(Str::random(10)),
            'sku' => 'Q-' . Str::upper(Str::random(8)),
            'type' => 'trading',
            'variant_mode' => 'simple',
            'is_active' => true,
            'is_sellable' => true,
            'is_web_listed' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'QV-' . Str::upper(Str::random(8)),
            'retail_price' => 500,
            'stock_quantity' => 5,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function test_about_page_renders(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('Why shop with us');
    }

    public function test_contact_page_renders(): void
    {
        $this->get(route('contact'))->assertOk()->assertSee('We’d love to hear from you', false);
    }

    public function test_contact_form_emails_the_team(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Asker',
            'email' => 'asker@test.local',
            'subject' => 'A question',
            'message' => 'Do you deliver to Lahore?',
        ])->assertRedirect(route('contact'));

        Mail::assertQueued(ContactMessageMail::class, fn (ContactMessageMail $m) => $m->hasReplyTo('asker@test.local'));
    }

    public function test_track_order_page_renders(): void
    {
        $this->get(route('track.order'))->assertOk()->assertSee('Track your order');
    }

    public function test_track_order_lookup_finds_a_matching_order(): void
    {
        $customer = Customer::create([
            'name' => 'Tracker', 'email' => 'track-' . Str::random(5) . '@test.local',
            'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'TRK-' . Str::upper(Str::random(6)),
            'customer_id' => $customer->id,
            'status' => 'shipped',
            'grand_total' => 500,
            'placed_at' => now(),
        ]);

        $this->post(route('track.order.lookup'), [
            'order_number' => $order->order_number,
            'email' => $customer->email,
        ])->assertOk()->assertSee($order->order_number)->assertSee('Shipped');
    }

    public function test_track_order_lookup_rejects_a_wrong_email(): void
    {
        $customer = Customer::create([
            'name' => 'Tracker', 'email' => 'right-' . Str::random(5) . '@test.local',
            'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'TRK-' . Str::upper(Str::random(6)),
            'customer_id' => $customer->id, 'status' => 'processing', 'grand_total' => 100, 'placed_at' => now(),
        ]);

        $this->post(route('track.order.lookup'), [
            'order_number' => $order->order_number,
            'email' => 'wrong@test.local',
        ])->assertOk()->assertSee('couldn’t find that order', false);
    }

    public function test_quote_search_returns_web_listed_products(): void
    {
        $variant = $this->webVariant('Zzq Searchable Quote Item');

        $this->getJson(route('quote.search', ['q' => 'Zzq Searchable']))
            ->assertOk()
            ->assertJsonFragment(['id' => $variant->id, 'name' => 'Zzq Searchable Quote Item']);
    }

    public function test_quote_request_with_items_creates_a_draft_with_line_items(): void
    {
        Mail::fake();
        $variant = $this->webVariant();
        $email = 'quoter-' . Str::random(5) . '@test.local';

        $this->post(route('quote.store'), [
            'name' => 'Quoter',
            'email' => $email,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 4],
            ],
        ])->assertRedirect(route('quote.request'));

        $customer = Customer::where('email', $email)->firstOrFail();
        $quotation = Quotation::where('customer_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame('draft', $quotation->status);
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'product_variant_id' => $variant->id,
            'quantity' => '4.000',
            'unit_price' => '0.00',
        ]);
        Mail::assertQueued(NewQuoteRequestMail::class);
    }

    public function test_quote_request_needs_items_or_a_message(): void
    {
        $this->post(route('quote.store'), [
            'name' => 'Empty',
            'email' => 'empty-' . Str::random(5) . '@test.local',
        ])->assertSessionHasErrors('message');
    }

    public function test_reorder_refills_the_cart_from_a_verified_order(): void
    {
        $variant = $this->webVariant();
        $customer = Customer::create([
            'name' => 'Repeat', 'email' => 'repeat-' . Str::random(5) . '@test.local',
            'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'RE-' . Str::upper(Str::random(6)),
            'customer_id' => $customer->id, 'status' => 'delivered', 'grand_total' => 1000, 'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id,
            'name_snapshot' => 'Quote Widget', 'sku_snapshot' => $variant->sku,
            'quantity' => 2, 'unit_price' => 500, 'line_total' => 1000,
        ]);

        $this->post(route('track.order.reorder'), [
            'order_number' => $order->order_number,
            'email' => $customer->email,
        ])->assertRedirect(route('cart'));

        $this->assertSame(2, session('cart')[$variant->id] ?? null);
    }

    public function test_reorder_is_rejected_without_the_matching_email(): void
    {
        $customer = Customer::create([
            'name' => 'Repeat', 'email' => 'owner-' . Str::random(5) . '@test.local',
            'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'RE-' . Str::upper(Str::random(6)),
            'customer_id' => $customer->id, 'status' => 'delivered', 'grand_total' => 10, 'placed_at' => now(),
        ]);

        $this->post(route('track.order.reorder'), [
            'order_number' => $order->order_number,
            'email' => 'stranger@test.local',
        ])->assertRedirect(route('track.order'));

        $this->assertNull(session('cart'));
    }

    public function test_account_reorder_refills_the_cart_for_the_owner(): void
    {
        $variant = $this->webVariant();
        $user = User::factory()->create(['is_active' => true]);
        $order = Order::create([
            'order_number' => 'ACC-' . Str::upper(Str::random(6)),
            'user_id' => $user->id, 'status' => 'delivered', 'grand_total' => 1500, 'placed_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id,
            'name_snapshot' => 'Quote Widget', 'sku_snapshot' => $variant->sku,
            'quantity' => 3, 'unit_price' => 500, 'line_total' => 1500,
        ]);

        $this->actingAs($user)
            ->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('cart'));

        $this->assertSame(3, session('cart')[$variant->id] ?? null);
    }

    public function test_account_reorder_blocks_other_peoples_orders(): void
    {
        $owner = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ACC-' . Str::upper(Str::random(6)),
            'user_id' => $owner->id, 'status' => 'delivered', 'grand_total' => 10, 'placed_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('account.orders.reorder', $order))
            ->assertNotFound();
    }
}
