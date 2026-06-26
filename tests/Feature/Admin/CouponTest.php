<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.coupons.index'))->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.coupons.index'))
            ->assertOk()->assertSee('Coupons');
    }

    public function test_admin_can_create_a_coupon_and_code_is_uppercased(): void
    {
        $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'welcome10', 'type' => 'percent', 'value' => '10', 'is_active' => '1',
        ])->assertRedirect(route('admin.coupons.index'));

        $coupon = Coupon::where('code', 'WELCOME10')->firstOrFail();
        $this->assertSame('percent', $coupon->type);
        $this->assertTrue($coupon->is_active);
    }

    public function test_percentage_over_100_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'TOOBIG', 'type' => 'percent', 'value' => '150', 'is_active' => '1',
        ])->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('coupons', ['code' => 'TOOBIG']);
    }

    public function test_expiry_must_be_after_start(): void
    {
        $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'BADDATES', 'type' => 'fixed', 'value' => '50',
            'starts_at' => '2026-08-01T00:00', 'expires_at' => '2026-07-01T00:00', 'is_active' => '1',
        ])->assertSessionHasErrors('expires_at');
    }

    public function test_admin_can_update_a_coupon(): void
    {
        $coupon = Coupon::create(['code' => 'OLD' . strtoupper(uniqid()), 'type' => 'fixed', 'value' => 20, 'is_active' => true]);

        $this->actingAs($this->admin())->put(route('admin.coupons.update', $coupon), [
            'code' => $coupon->code, 'type' => 'fixed', 'value' => '35', 'is_active' => '1',
        ])->assertRedirect(route('admin.coupons.index'));

        $this->assertSame('35.00', (string) $coupon->fresh()->value);
    }
}
