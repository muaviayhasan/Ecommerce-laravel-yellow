<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.categories.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Categories')
            ->assertSee('Add category');
    }

    public function test_storing_a_category_auto_generates_a_slug(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Shoes & Bags', 'is_active' => '1'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Shoes & Bags', 'slug' => 'shoes-bags', 'is_active' => true]);
    }

    public function test_duplicate_names_get_a_unique_slug(): void
    {
        Category::create(['name' => 'Shoes', 'slug' => 'shoes']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Shoes'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['slug' => 'shoes-2']);
    }

    public function test_updating_a_category_persists_changes(): void
    {
        $category = Category::create(['name' => 'Old', 'slug' => 'old']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), ['name' => 'New name', 'slug' => 'old', 'sort_order' => 5])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New name', 'sort_order' => 5]);
    }

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $category = Category::create(['name' => 'Self', 'slug' => 'self']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), ['name' => 'Self', 'parent_id' => $category->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_deleting_a_category_reparents_children(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => null]);
    }

    public function test_cannot_delete_a_category_with_products(): void
    {
        $category = Category::create(['name' => 'Has products', 'slug' => 'has-products']);
        Product::create(['name' => 'P1', 'slug' => 'p1-' . uniqid(), 'sku' => 'SKU-' . uniqid(), 'type' => 'trading', 'category_id' => $category->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_requires_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = Category::create(['name' => 'Guarded', 'slug' => 'guarded']);

        $this->actingAs($user)
            ->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();
    }
}
