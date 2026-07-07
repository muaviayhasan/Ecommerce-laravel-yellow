<?php

namespace Tests\Feature\Admin;

use App\Models\AbandonedCart;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AbandonedCartAdminTest extends TestCase
{
    // Roll back any writes — these tests run against the configured (MySQL) DB.
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::role('super-admin')->first();
        $this->assertNotNull($admin, 'Seeded super-admin is missing — run db:seed.');

        return $admin;
    }

    private function cart(array $overrides = []): AbandonedCart
    {
        return AbandonedCart::create(array_merge([
            'email' => 'shopper@test.local',
            'name' => 'Shopper',
            'items' => [['variant_id' => 1, 'qty' => 2]],
            'subtotal' => 250,
            'item_count' => 2,
        ], $overrides));
    }

    public function test_super_admin_sees_the_recovery_dashboard(): void
    {
        $this->cart(['email' => 'listed@test.local']);

        $this->actingAs($this->admin())
            ->get(route('admin.abandoned-carts.index'))
            ->assertOk()
            ->assertSee('Open carts')
            ->assertSee('Recovery rate')
            ->assertSee('listed@test.local');
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $this->cart(['email' => 'open-one@test.local']);
        $this->cart(['email' => 'won-one@test.local', 'recovered_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.abandoned-carts.index', ['status' => 'recovered']))
            ->assertOk()
            ->assertSee('won-one@test.local')
            ->assertDontSee('open-one@test.local');
    }

    public function test_a_cart_can_be_removed(): void
    {
        $cart = $this->cart();

        $this->actingAs($this->admin())
            ->delete(route('admin.abandoned-carts.destroy', $cart))
            ->assertRedirect();

        $this->assertDatabaseMissing('abandoned_carts', ['id' => $cart->id]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.abandoned-carts.index'))
            ->assertForbidden();
    }
}
