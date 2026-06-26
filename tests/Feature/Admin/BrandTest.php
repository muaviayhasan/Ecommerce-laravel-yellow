<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BrandTest extends TestCase
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
            ->get(route('admin.brands.index'))->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.brands.index'))
            ->assertOk()->assertSee('Brands');
    }

    public function test_admin_can_create_a_brand_with_an_auto_slug(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.brands.store'), ['name' => 'Acme Gear', 'slug' => '', 'is_active' => '1'])
            ->assertRedirect(route('admin.brands.index'));

        $brand = Brand::where('name', 'Acme Gear')->firstOrFail();
        $this->assertSame('acme-gear', $brand->slug);
        $this->assertTrue($brand->is_active);
    }

    public function test_admin_can_update_a_brand(): void
    {
        $brand = Brand::create(['name' => 'Before', 'slug' => 'before-' . uniqid(), 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.brands.update', $brand), ['name' => 'After', 'slug' => $brand->slug, 'is_active' => '1'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertSame('After', $brand->fresh()->name);
    }

    public function test_a_brand_with_products_cannot_be_deleted(): void
    {
        $brand = Brand::create(['name' => 'Has Products', 'slug' => 'hp-' . uniqid(), 'is_active' => true]);
        Product::create([
            'category_id' => Category::value('id'), 'brand_id' => $brand->id,
            'name' => 'P ' . uniqid(), 'slug' => 'p-' . uniqid(), 'sku' => 'P-' . uniqid(),
            'type' => 'trading', 'variant_mode' => 'simple', 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect();

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }
}
