<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The seven export-only modules (quotations, orders, reviews, blog posts,
 * blog categories, reports, ledger) share one controller/exporter pattern —
 * exercised here across all of them: RBAC, Excel download, PDF download.
 */
class ExportOnlyModulesTest extends TestCase
{
    use DatabaseTransactions;

    /** route name => permission */
    private const MODULES = [
        'admin.quotations.export' => 'quotations.export',
        'admin.orders.export' => 'orders.export',
        'admin.reviews.export' => 'reviews.export',
        'admin.blog.posts.export' => 'blog-posts.export',
        'admin.blog.categories.export' => 'blog-categories.export',
        'admin.reports.export' => 'reports.export',
        'admin.ledger.export' => 'ledger.export',
    ];

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guest_is_redirected_to_admin_login_on_every_export(): void
    {
        foreach (self::MODULES as $route => $permission) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_user_without_permission_is_forbidden_on_every_export(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach (self::MODULES as $route => $permission) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_every_module_downloads_an_xlsx(): void
    {
        foreach (self::MODULES as $route => $permission) {
            $response = $this->actingAs($this->admin())->get(route($route));

            $response->assertOk();
            $this->assertStringContainsString(
                'spreadsheetml',
                (string) $response->headers->get('content-type'),
                "Excel export failed for {$route}"
            );
        }
    }

    public function test_every_module_downloads_a_pdf(): void
    {
        foreach (self::MODULES as $route => $permission) {
            $response = $this->actingAs($this->admin())->get(route($route, ['format' => 'pdf']));

            $response->assertOk();
            $this->assertStringContainsString(
                'application/pdf',
                (string) $response->headers->get('content-type'),
                "PDF export failed for {$route}"
            );
        }
    }
}
