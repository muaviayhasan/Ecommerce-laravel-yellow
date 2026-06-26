<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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
            ->get(route('admin.activity.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_log(): void
    {
        $this->actingAs($this->admin())->get(route('admin.activity.index'))
            ->assertOk()->assertSee('Activity log');
    }

    public function test_creating_an_audited_model_is_logged(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.brands.store'), ['name' => 'Audited Co', 'is_active' => '1'])
            ->assertRedirect();

        $brand = Brand::where('name', 'Audited Co')->firstOrFail();
        $log = ActivityLog::where('event', 'created')
            ->where('subject_type', Brand::class)->where('subject_id', $brand->id)->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Audited Co', $log->properties['after']['name']);
    }

    public function test_updating_records_a_before_and_after(): void
    {
        $brand = Brand::create(['name' => 'Old Name', 'slug' => 'old-' . uniqid(), 'is_active' => true]);

        $this->actingAs($this->admin())->put(route('admin.brands.update', $brand), [
            'name' => 'New Name', 'slug' => $brand->slug, 'is_active' => '1',
        ])->assertRedirect();

        $log = ActivityLog::where('event', 'updated')
            ->where('subject_type', Brand::class)->where('subject_id', $brand->id)->latest('id')->firstOrFail();

        $this->assertSame('Old Name', $log->properties['before']['name']);
        $this->assertSame('New Name', $log->properties['after']['name']);
    }

    public function test_unauthenticated_changes_are_not_logged(): void
    {
        $before = ActivityLog::count();

        // No acting-as → simulates a seeder / console action.
        Brand::create(['name' => 'Silent', 'slug' => 'silent-' . uniqid(), 'is_active' => true]);

        $this->assertSame($before, ActivityLog::count());
    }
}
