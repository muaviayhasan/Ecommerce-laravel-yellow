<?php

namespace Tests\Feature\Admin;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acme Supplies ' . uniqid(),
            'company' => 'Acme Pvt Ltd',
            'phone' => '0301-1234567',
            'email' => 'acme@example.test',
            'opening_balance' => '0',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('admin.suppliers.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.suppliers.index'))->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.suppliers.index'))
            ->assertOk()->assertSee('Suppliers');
    }

    public function test_admin_can_create_a_supplier(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.suppliers.store'), $this->payload(['name' => 'Bolt Source']))
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['name' => 'Bolt Source', 'is_active' => true]);
    }

    public function test_admin_can_update_a_supplier(): void
    {
        $supplier = Supplier::create($this->payload(['name' => 'Before']));

        $this->actingAs($this->admin())
            ->put(route('admin.suppliers.update', $supplier), $this->payload(['name' => 'After']))
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertSame('After', $supplier->fresh()->name);
    }

    public function test_supplier_with_purchases_cannot_be_deleted(): void
    {
        $supplier = Supplier::create($this->payload());
        Purchase::create([
            'purchase_number' => 'PUR-' . uniqid(), 'supplier_id' => $supplier->id, 'status' => 'draft',
            'purchase_date' => now(), 'subtotal' => 0, 'tax_total' => 0, 'grand_total' => 0, 'paid_total' => 0,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.suppliers.destroy', $supplier))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
