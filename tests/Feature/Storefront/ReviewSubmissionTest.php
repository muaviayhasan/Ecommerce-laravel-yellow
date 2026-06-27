<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    private function product(): Product
    {
        $product = Product::create([
            'category_id' => Category::value('id'),
            'name' => 'Reviewable ' . uniqid(), 'slug' => 'rv-' . uniqid(), 'sku' => 'RV-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple',
            'is_active' => true, 'is_sellable' => true, 'is_web_listed' => true, 'published_at' => now()->subDay(),
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'RVV-' . uniqid(),
            'retail_price' => 500, 'cost' => 200, 'stock_quantity' => 5, 'is_default' => true, 'is_active' => true,
        ]);

        return $product;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $product = $this->product();

        $this->post(route('product.reviews.store', $product->slug), ['rating' => 5, 'body' => 'Nice'])
            ->assertRedirect(route('login'));
    }

    public function test_a_logged_in_customer_submits_a_pending_review(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('product.reviews.store', $product->slug), [
            'rating' => 5, 'title' => 'Great', 'body' => 'Really loved it',
        ])->assertRedirect();

        $review = Review::where('product_id', $product->id)->where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($review->is_approved);   // enters the moderation queue
        $this->assertSame(5, $review->rating);
    }

    public function test_pending_review_does_not_appear_in_the_public_list(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->post(route('product.reviews.store', $product->slug), ['rating' => 4, 'body' => 'Pending body text']);

        // A different visitor must not see the unapproved review.
        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Based on 0 reviews'); // no approved reviews yet
    }

    public function test_resubmitting_updates_the_same_review(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('product.reviews.store', $product->slug), ['rating' => 5, 'body' => 'first version']);
        $this->actingAs($user)->post(route('product.reviews.store', $product->slug), ['rating' => 2, 'body' => 'second version']);

        $reviews = Review::where('product_id', $product->id)->where('user_id', $user->id)->get();
        $this->assertCount(1, $reviews);
        $this->assertSame(2, $reviews->first()->rating);
        $this->assertSame('second version', $reviews->first()->body);
    }

    public function test_validation_requires_a_rating_and_body(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('product.reviews.store', $product->slug), [])
            ->assertSessionHasErrors(['rating', 'body']);
    }
}
