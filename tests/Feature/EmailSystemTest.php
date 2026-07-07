<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignJob;
use App\Mail\Admin\NewQuoteRequestMail;
use App\Mail\Admin\NewSubscriberMail;
use App\Mail\CampaignMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\PasswordChangedMail;
use App\Mail\QuotationSentMail;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\Customer;
use App\Models\EmailCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsApplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailSystemTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Test Buyer',
            'email' => 'buyer-' . Str::random(6) . '@test.local',
            'type' => 'retail',
            'price_tier' => 'retail',
            'is_active' => true,
        ], $overrides));
    }

    private function order(Customer $customer): Order
    {
        return Order::create([
            'order_number' => 'TST-' . Str::upper(Str::random(6)),
            'channel' => 'web',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'grand_total' => 100,
            'paid_total' => 0,
            'currency' => 'PKR',
        ]);
    }

    // --- Newsletter -----------------------------------------------------------

    public function test_newsletter_signup_stores_subscriber_and_alerts_admin(): void
    {
        Mail::fake();

        $email = 'sub-' . Str::random(6) . '@test.local';
        $this->post(route('newsletter.subscribe'), ['email' => $email])->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => $email, 'unsubscribed_at' => null]);
        Mail::assertQueued(NewSubscriberMail::class);
    }

    public function test_repeat_signup_does_not_realert(): void
    {
        $email = 'sub-' . Str::random(6) . '@test.local';
        NewsletterSubscriber::create(['email' => $email]);

        Mail::fake();
        $this->post(route('newsletter.subscribe'), ['email' => $email])->assertRedirect();

        Mail::assertNotQueued(NewSubscriberMail::class);
    }

    public function test_unsubscribe_link_marks_subscriber_inactive(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'sub-' . Str::random(6) . '@test.local']);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk();

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    // --- Request a quote ------------------------------------------------------

    public function test_quote_request_creates_draft_quotation_and_alerts_staff(): void
    {
        Mail::fake();

        $email = 'lead-' . Str::random(6) . '@test.local';
        $this->post(route('quote.store'), [
            'name' => 'Lead Person',
            'email' => $email,
            'message' => 'Please quote 10 units of product X.',
        ])->assertRedirect();

        $customer = Customer::where('email', $email)->firstOrFail();
        $this->assertDatabaseHas('quotations', ['customer_id' => $customer->id, 'status' => 'draft']);
        Mail::assertQueued(NewQuoteRequestMail::class);
    }

    // --- Auth flows -----------------------------------------------------------

    public function test_registration_queues_welcome_and_verification_emails(): void
    {
        Mail::fake();

        $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'newcust-' . Str::random(6) . '@test.local',
            'password' => 'password1234',
            'terms' => '1',
        ])->assertRedirect();

        Mail::assertQueued(WelcomeMail::class);
        Mail::assertQueued(VerifyEmailMail::class);
    }

    public function test_forgot_password_queues_reset_email(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        Mail::fake();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

        Mail::assertQueued(ResetPasswordMail::class);
    }

    public function test_password_change_queues_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'oldpass1234', 'is_active' => true]);

        Mail::fake();
        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'oldpass1234',
            'password' => 'newpass1234',
            'password_confirmation' => 'newpass1234',
        ])->assertRedirect();

        Mail::assertQueued(PasswordChangedMail::class);
    }

    // --- Orders & quotations --------------------------------------------------

    public function test_order_status_change_emails_the_customer(): void
    {
        $order = $this->order($this->customer());

        Mail::fake();
        $this->actingAs($this->admin())->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();

        Mail::assertQueued(OrderStatusUpdatedMail::class);
    }

    public function test_marking_a_quotation_sent_emails_the_customer(): void
    {
        $customer = $this->customer();
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . Str::upper(Str::random(6)),
            'customer_id' => $customer->id,
            'status' => 'draft',
            'price_tier' => 'retail',
            'subtotal' => 50,
            'grand_total' => 50,
        ]);
        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'name_snapshot' => 'Sample item',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
        ]);

        Mail::fake();
        $this->actingAs($this->admin())->post(route('admin.quotations.status', $quotation), ['status' => 'sent'])
            ->assertRedirect();

        Mail::assertQueued(QuotationSentMail::class);
    }

    // --- Campaigns ------------------------------------------------------------

    public function test_campaign_send_queues_mail_to_active_subscribers_only(): void
    {
        // Isolate from any subscribers already in the (shared, non-refreshed) DB so
        // the audience is exactly the three created here. Rolled back with the test.
        NewsletterSubscriber::query()->delete();

        $active1 = NewsletterSubscriber::create(['email' => 'a-' . Str::random(6) . '@test.local']);
        $active2 = NewsletterSubscriber::create(['email' => 'b-' . Str::random(6) . '@test.local']);
        $gone = NewsletterSubscriber::create(['email' => 'c-' . Str::random(6) . '@test.local', 'unsubscribed_at' => now()]);

        $campaign = EmailCampaign::create([
            'subject' => 'Weekend sale',
            'body' => 'Hi {{ name }}, enjoy the sale!',
            'audience' => 'subscribers',
            'status' => 'draft',
        ]);

        Mail::fake();
        (new SendCampaignJob($campaign->id))->handle();

        // Both active subscribers were emailed; the unsubscribed one was not.
        Mail::assertQueued(CampaignMail::class, 2);
        Mail::assertQueued(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo($active1->email));
        Mail::assertNotQueued(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo($gone->email));

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
    }

    // --- Settings -------------------------------------------------------------

    public function test_mail_settings_apply_to_runtime_config(): void
    {
        Setting::putGroup('mail', [
            'host' => 'smtp.example-test.com',
            'port' => 2525,
            'username' => 'user@x.test',
            'password' => 'secret',
            'encryption' => 'ssl',
            'from_address' => 'from@x.test',
            'from_name' => 'Store',
        ], [
            'host' => 'string', 'port' => 'int', 'username' => 'string',
            'password' => 'encrypted', 'encryption' => 'string',
            'from_address' => 'string', 'from_name' => 'string',
        ]);

        SettingsApplier::apply();

        $this->assertSame('smtp.example-test.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('from@x.test', config('mail.from.address'));
    }

    public function test_email_notification_toggles_persist(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'emails'), [
            // order_confirmation intentionally omitted → resolves to off.
            'registration_welcome' => '1',
            'order_status_update' => '1',
        ])->assertRedirect();

        $this->assertSame('0', Setting::where('group', 'emails')->where('key', 'order_confirmation')->value('value'));
        $this->assertSame('1', Setting::where('group', 'emails')->where('key', 'registration_welcome')->value('value'));
    }
}
