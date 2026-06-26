<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function review(bool $approved = false): Review
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'R ' . uniqid(), 'slug' => 'r-' . uniqid(), 'sku' => 'R-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true,
        ]);

        return Review::create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4, 'title' => 'Good', 'body' => 'Works well.', 'is_approved' => $approved,
        ]);
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.reviews.index'))->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.reviews.index'))
            ->assertOk()->assertSee('Product reviews');
    }

    public function test_admin_can_approve_a_pending_review(): void
    {
        $review = $this->review(approved: false);

        $this->actingAs($this->admin())
            ->patch(route('admin.reviews.approve', $review))
            ->assertRedirect();

        $this->assertTrue($review->fresh()->is_approved);
    }

    public function test_admin_can_unapprove_a_review(): void
    {
        $review = $this->review(approved: true);

        $this->actingAs($this->admin())
            ->patch(route('admin.reviews.unapprove', $review))
            ->assertRedirect();

        $this->assertFalse($review->fresh()->is_approved);
    }

    public function test_admin_can_delete_a_review(): void
    {
        $review = $this->review();

        $this->actingAs($this->admin())
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
