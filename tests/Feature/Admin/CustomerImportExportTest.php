<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use App\Services\ImportExport\Exporters\CustomerExporter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Admin\Concerns\BuildsXlsx;
use Tests\TestCase;

class CustomerImportExportTest extends TestCase
{
    use BuildsXlsx;
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.customers.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.customers.import'))->assertForbidden();
    }

    public function test_export_downloads_an_xlsx(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.customers.export'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
    }

    public function test_import_upserts_by_email(): void
    {
        $existing = Customer::create(['name' => 'Old Customer', 'email' => 'cust-' . uniqid() . '@example.com', 'type' => 'retail', 'price_tier' => 'retail']);

        $file = $this->xlsxUpload(CustomerExporter::HEADERS, [
            ['Updated Customer', $existing->email, '', '', 'wholesale', 'wholesale', 150, 1, ''],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.customers.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['updated'] === 1 && $r['failed'] === 0);

        $fresh = $existing->fresh();
        $this->assertSame('Updated Customer', $fresh->name);
        $this->assertSame('wholesale', $fresh->type);
        $this->assertSame(150.0, (float) $fresh->opening_balance);
    }

    public function test_import_matches_by_phone_when_email_is_blank(): void
    {
        $phone = '03' . random_int(100000000, 999999999);
        $existing = Customer::create(['name' => 'Phone Match', 'phone' => $phone, 'type' => 'retail', 'price_tier' => 'retail']);

        $file = $this->xlsxUpload(CustomerExporter::HEADERS, [
            ['Phone Match Updated', '', $phone, '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin())->post(route('admin.customers.import.store'), ['file' => $file]);

        $this->assertSame('Phone Match Updated', $existing->fresh()->name);
    }

    public function test_import_creates_when_no_match_keys_are_present(): void
    {
        $name = 'Walk-in ' . uniqid();

        $file = $this->xlsxUpload(CustomerExporter::HEADERS, [
            [$name, '', '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.customers.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1);

        $this->assertDatabaseHas('customers', ['name' => $name, 'type' => 'retail']);
    }

    public function test_ambiguous_email_match_fails_the_row(): void
    {
        $email = 'dup-' . uniqid() . '@example.com';
        Customer::create(['name' => 'Dup A', 'email' => $email, 'type' => 'retail', 'price_tier' => 'retail']);
        Customer::create(['name' => 'Dup B', 'email' => $email, 'type' => 'retail', 'price_tier' => 'retail']);

        $file = $this->xlsxUpload(CustomerExporter::HEADERS, [
            ['Whoever', $email, '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.customers.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1 && str_contains($r['errors'][0]['message'], 'Ambiguous'));
    }
}
