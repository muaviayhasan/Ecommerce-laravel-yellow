<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    // Roll back any writes — these tests run against the configured (MySQL) DB.
    use DatabaseTransactions;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_the_dashboard(): void
    {
        $admin = User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->first();

        $this->assertNotNull($admin, 'Seeded super-admin is missing — run db:seed.');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Total Sales')
            ->assertSee('Recent Orders')
            ->assertSee('Earnings');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
