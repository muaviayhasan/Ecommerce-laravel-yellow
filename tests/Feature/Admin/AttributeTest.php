<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.attributes.index'))->assertRedirect(route('admin.login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.attributes.index'))->assertForbidden();
    }

    public function test_admin_can_view_the_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSee('Attributes')
            ->assertSee('Add attribute');
    }

    public function test_storing_creates_attribute_with_values_and_auto_codes(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.attributes.store'), [
                'name' => 'Colour',
                'type' => 'swatch',
                'is_variation' => '1',
                'sort_order' => 0,
                'values' => [
                    ['id' => '', 'label' => 'Red', 'value' => '', 'color_hex' => '#ff0000', 'sort_order' => 0],
                    ['id' => '', 'label' => 'Royal Blue', 'value' => 'blue', 'color_hex' => '#0000ff', 'sort_order' => 1],
                    ['id' => '', 'label' => '', 'value' => '', 'color_hex' => '#000000', 'sort_order' => 2], // blank → skipped
                ],
            ])
            ->assertRedirect(route('admin.attributes.index'));

        $attribute = Attribute::where('code', 'colour')->firstOrFail();
        $this->assertTrue($attribute->is_variation);
        $this->assertCount(2, $attribute->values);

        $red = $attribute->values->firstWhere('label', 'Red');
        $this->assertSame('red', $red->value);          // auto-slugged from label
        $this->assertSame('#ff0000', $red->color_hex);  // swatch keeps colour
        $this->assertSame('blue', $attribute->values->firstWhere('label', 'Royal Blue')->value);
    }

    public function test_colour_is_not_stored_for_non_swatch_types(): void
    {
        $code = 'sz-' . uniqid();

        $this->actingAs($this->admin())
            ->post(route('admin.attributes.store'), [
                'name' => 'Size',
                'code' => $code,
                'type' => 'select',
                'sort_order' => 0,
                'values' => [['id' => '', 'label' => 'Small', 'value' => 's', 'color_hex' => '#123456', 'sort_order' => 0]],
            ])
            ->assertRedirect();

        $value = Attribute::where('code', $code)->firstOrFail()->values->first();
        $this->assertNull($value->color_hex);
    }

    public function test_updating_syncs_values_add_edit_remove(): void
    {
        $code = 'size-' . uniqid();
        $attribute = Attribute::create(['name' => 'Size', 'code' => $code, 'type' => 'select', 'is_variation' => true, 'sort_order' => 0]);
        $small = $attribute->values()->create(['value' => 's', 'label' => 'Small', 'sort_order' => 0]);
        $medium = $attribute->values()->create(['value' => 'm', 'label' => 'Medium', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.attributes.update', $attribute), [
                'name' => 'Size',
                'code' => $code,
                'type' => 'select',
                'is_variation' => '1',
                'sort_order' => 0,
                'values' => [
                    ['id' => $small->id, 'label' => 'Small (S)', 'value' => 's', 'sort_order' => 0], // keep + edit
                    ['id' => '', 'label' => 'Large', 'value' => 'l', 'sort_order' => 1],             // add
                    // medium omitted → removed
                ],
            ])
            ->assertRedirect(route('admin.attributes.index'));

        $attribute->refresh()->load('values');
        $this->assertCount(2, $attribute->values);
        $this->assertSame('Small (S)', $small->fresh()->label);
        $this->assertDatabaseMissing('attribute_values', ['id' => $medium->id]);
        $this->assertTrue($attribute->values->contains('label', 'Large'));
    }

    public function test_deleting_removes_attribute_and_its_values(): void
    {
        $attribute = Attribute::create(['name' => 'Material', 'code' => 'material-' . uniqid(), 'type' => 'select', 'sort_order' => 0]);
        $value = $attribute->values()->create(['value' => 'cotton', 'label' => 'Cotton', 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    }

    public function test_deleting_requires_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $attribute = Attribute::create(['name' => 'Guarded', 'code' => 'guarded-' . uniqid(), 'type' => 'select', 'sort_order' => 0]);

        $this->actingAs($user)
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertForbidden();
    }
}
