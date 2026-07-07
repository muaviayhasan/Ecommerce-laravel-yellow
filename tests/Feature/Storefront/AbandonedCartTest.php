<?php

namespace Tests\Feature\Storefront;

use App\Jobs\SendAbandonedCartRemindersJob;
use App\Mail\AbandonedCartMail;
use App\Models\AbandonedCart;
use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\AbandonedCartService;
use App\Support\Mail\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/**
 * Each test runs in its own process: the setting() helper caches settings for the
 * life of the PHP process, and an earlier test in the suite warms the "emails"
 * group — isolation guarantees the feature toggles below are read fresh.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AbandonedCartTest extends TestCase
{
    use DatabaseTransactions;

    private function enableFeature(array $overrides = []): void
    {
        Setting::putGroup('emails', array_merge([
            'abandoned_cart' => true,
            'abandoned_cart_first_delay_hours' => 1,
            'abandoned_cart_followup_delay_hours' => 20,
            'abandoned_cart_max_reminders' => 2,
            'abandoned_cart_coupon_id' => 0,
        ], $overrides), [
            'abandoned_cart' => 'bool',
            'abandoned_cart_first_delay_hours' => 'int',
            'abandoned_cart_followup_delay_hours' => 'int',
            'abandoned_cart_max_reminders' => 'int',
            'abandoned_cart_coupon_id' => 'int',
        ]);
    }

    private function sellableVariant(float $price = 100, float $stock = 10): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::query()->value('id'),
            'name' => 'Test Widget',
            'slug' => 'test-widget-' . Str::lower(Str::random(10)),
            'sku' => 'TW-' . Str::upper(Str::random(8)),
            'type' => 'trading',
            'variant_mode' => 'simple',
            'is_active' => true,
            'is_sellable' => true,
            'is_web_listed' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TWV-' . Str::upper(Str::random(8)),
            'retail_price' => $price,
            'stock_quantity' => $stock,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /** A minimal snapshot line for the service. */
    private function line(ProductVariant $variant, int $qty = 2): array
    {
        return [[
            'variant_id' => $variant->id,
            'name' => 'Test Widget',
            'sku' => $variant->sku,
            'qty' => $qty,
            'price' => (float) $variant->retail_price,
            'image' => null,
            'url' => '/shop',
        ]];
    }

    public function test_guest_checkout_email_capture_stores_the_cart(): void
    {
        $this->enableFeature();
        $variant = $this->sellableVariant();

        $this->withSession(['cart' => [$variant->id => 2]])
            ->postJson(route('checkout.capture'), ['email' => 'Guest@Test.Local', 'name' => 'Guest'])
            ->assertNoContent();

        $this->assertDatabaseHas('abandoned_carts', [
            'email' => 'guest@test.local', // normalised to lower-case
            'item_count' => 2,
        ]);
    }

    public function test_capture_is_a_noop_while_the_feature_is_off(): void
    {
        $this->enableFeature(['abandoned_cart' => false]);
        $variant = $this->sellableVariant();

        $this->withSession(['cart' => [$variant->id => 1]])
            ->postJson(route('checkout.capture'), ['email' => 'off@test.local'])
            ->assertNoContent();

        $this->assertDatabaseMissing('abandoned_carts', ['email' => 'off@test.local']);
    }

    public function test_the_reminder_job_emails_a_due_cart_on_the_low_queue(): void
    {
        $this->enableFeature();
        Mail::fake();
        $variant = $this->sellableVariant();

        $cart = app(AbandonedCartService::class)
            ->capture('due@test.local', $this->line($variant), 200, 'Buyer');

        // Age it past the first-reminder delay so the sweep considers it due.
        AbandonedCart::whereKey($cart->id)->update([
            'updated_at' => now()->subHours(3),
            'created_at' => now()->subHours(3),
        ]);

        (new SendAbandonedCartRemindersJob)->handle();

        Mail::assertQueued(
            AbandonedCartMail::class,
            fn (AbandonedCartMail $m) => $m->cart->id === $cart->id && $m->queue === 'low',
        );

        $fresh = $cart->fresh();
        $this->assertSame(1, $fresh->reminders_sent);
        $this->assertNotNull($fresh->last_reminded_at);
    }

    public function test_the_job_stops_at_the_reminder_ceiling(): void
    {
        $this->enableFeature(['abandoned_cart_max_reminders' => 1]);
        Mail::fake();
        $variant = $this->sellableVariant();

        $cart = app(AbandonedCartService::class)
            ->capture('maxed@test.local', $this->line($variant), 200, 'Buyer');

        // Already reminded once, and past the follow-up window — but max is 1.
        AbandonedCart::whereKey($cart->id)->update([
            'reminders_sent' => 1,
            'last_reminded_at' => now()->subHours(48),
            'updated_at' => now()->subHours(48),
            'created_at' => now()->subHours(48),
        ]);

        (new SendAbandonedCartRemindersJob)->handle();

        Mail::assertNothingQueued();
    }

    public function test_a_recovered_cart_is_not_reminded(): void
    {
        $this->enableFeature();
        Mail::fake();
        $variant = $this->sellableVariant();

        $service = app(AbandonedCartService::class);
        $cart = $service->capture('bought@test.local', $this->line($variant), 200, 'Buyer');
        AbandonedCart::whereKey($cart->id)->update([
            'updated_at' => now()->subHours(3),
            'created_at' => now()->subHours(3),
        ]);

        // Placing the order closes the recovery.
        $service->markRecovered('bought@test.local');

        (new SendAbandonedCartRemindersJob)->handle();

        Mail::assertNothingQueued();
        $this->assertNotNull($cart->fresh()->recovered_at);
    }

    public function test_an_unsubscribed_shopper_is_suppressed(): void
    {
        $this->enableFeature();
        Mail::fake();
        $variant = $this->sellableVariant();

        NewsletterSubscriber::create([
            'email' => 'nope@test.local',
            'unsubscribed_at' => now(),
        ]);

        $cart = app(AbandonedCartService::class)
            ->capture('nope@test.local', $this->line($variant), 200, 'Buyer');
        AbandonedCart::whereKey($cart->id)->update([
            'updated_at' => now()->subHours(3),
            'created_at' => now()->subHours(3),
        ]);

        (new SendAbandonedCartRemindersJob)->handle();

        Mail::assertNothingQueued();
    }

    public function test_recover_link_rebuilds_the_cart_and_returns_to_checkout(): void
    {
        $variant = $this->sellableVariant();

        $cart = AbandonedCart::create([
            'email' => 'return@test.local',
            'items' => [['variant_id' => $variant->id, 'qty' => 3]],
            'subtotal' => 300,
            'item_count' => 3,
            'token' => Str::random(48),
        ]);

        $response = $this->get(route('cart.recover', $cart->token));

        $response->assertRedirect(route('checkout'));
        $this->assertSame(3, session('cart')[$variant->id] ?? null);
    }

    public function test_transactional_mail_rides_the_high_queue(): void
    {
        Mail::fake();

        Notifier::send('some_transactional_notice', 'x@test.local', new ProbeMail);

        Mail::assertQueued(ProbeMail::class, fn (ProbeMail $m) => $m->queue === 'high');
    }
}

/**
 * A minimal queueable mailable used only to prove Notifier assigns the high queue
 * to a transactional message that hasn't picked a queue of its own.
 */
class ProbeMail extends Mailable implements ShouldQueue
{
    use \Illuminate\Queue\SerializesModels, Queueable;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'probe');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>probe</p>');
    }
}
