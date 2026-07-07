<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function typed(string $group, string $key): mixed
    {
        return Setting::where('group', $group)->where('key', $key)->first()?->typed_value;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/settings/general')->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/admin/settings/general')->assertForbidden();
    }

    public function test_admin_can_view_the_general_tab(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/settings/general')
            ->assertOk()
            ->assertSee('Store name')
            ->assertSee('Currency code')
            ->assertSee('Timezone');
    }

    public function test_payment_tab_renders_with_blank_secrets(): void
    {
        // Regression: blank `encrypted` settings must decode to null, not throw.
        $this->actingAs($this->admin())
            ->get('/admin/settings/payment')
            ->assertOk()
            ->assertSee('Cash on Delivery')
            ->assertSee('Merchant ID');
    }

    public function test_unknown_group_404s(): void
    {
        $this->actingAs($this->admin())->get('/admin/settings/not-a-group')->assertNotFound();
    }

    public function test_updating_mail_persists_values(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/settings/mail', [
                'from_name' => 'Usman Store',
                'from_address' => 'hello@usman.test',
            ])
            ->assertRedirect();

        $this->assertSame('Usman Store', $this->typed('mail', 'from_name'));
        $this->assertSame('hello@usman.test', $this->typed('mail', 'from_address'));
    }

    public function test_toggles_resolve_to_real_booleans(): void
    {
        // cod on (checkbox present), qr omitted → must store an explicit false.
        $this->actingAs($this->admin())
            ->put('/admin/settings/payment', ['cod_enabled' => '1'])
            ->assertRedirect();

        $this->assertTrue($this->typed('payment', 'cod_enabled'));
        $this->assertFalse($this->typed('payment', 'qr_enabled'));
    }

    public function test_secret_is_encrypted_and_kept_on_blank(): void
    {
        $admin = $this->admin();

        // Save a secret → stored encrypted, decodes back to plaintext.
        $this->actingAs($admin)->put('/admin/settings/payment', [
            'jazzcash_merchant_id' => 'MID-SECRET-123',
        ])->assertRedirect();

        $row = Setting::where('group', 'payment')->where('key', 'jazzcash_merchant_id')->first();
        $this->assertSame('encrypted', $row->type);
        $this->assertNotSame('MID-SECRET-123', $row->value, 'Secret must not be stored in plaintext.');
        $this->assertSame('MID-SECRET-123', $row->typed_value);

        // Submit blank → existing secret is kept, not wiped.
        $this->actingAs($admin)->put('/admin/settings/payment', [
            'jazzcash_merchant_id' => '',
        ])->assertRedirect();

        $this->assertSame('MID-SECRET-123', $this->typed('payment', 'jazzcash_merchant_id'));
    }

    public function test_update_requires_edit_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->put('/admin/settings/mail', ['from_name' => 'X', 'from_address' => 'x@y.test'])
            ->assertForbidden();
    }

    /** @dataProvider newTabs */
    public function test_new_operational_tabs_render(string $group, string $expect): void
    {
        $this->actingAs($this->admin())->get("/admin/settings/{$group}")->assertOk()->assertSee($expect);
    }

    public static function newTabs(): array
    {
        return [
            ['inventory', 'Allow negative stock'],
            ['pricing', 'Default markup (%)'],
            ['numbering', 'Order prefix'],
            ['pos', 'Receipt footer'],
            ['quotation', 'Valid for (days)'],
            ['social_login', 'Enable Google sign-in'],
        ];
    }

    public function test_updating_numbering_persists_prefixes(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings/numbering', [
            'order_prefix' => 'SO-', 'quotation_prefix' => 'QT-',
            'purchase_prefix' => 'PO-', 'production_prefix' => 'MO-',
        ])->assertRedirect();

        $this->assertSame('SO-', $this->typed('numbering', 'order_prefix'));
        $this->assertSame('MO-', $this->typed('numbering', 'production_prefix'));
    }

    public function test_updating_inventory_persists_costing_and_negative_flag(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings/inventory', [
            'costing_method' => 'moving_average', 'allow_negative_stock' => '1',
        ])->assertRedirect();

        $this->assertSame('moving_average', $this->typed('inventory', 'costing_method'));
        $this->assertTrue($this->typed('inventory', 'allow_negative_stock'));
    }
}
