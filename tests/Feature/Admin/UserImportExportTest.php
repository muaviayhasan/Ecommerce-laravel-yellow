<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\ImportExport\Exporters\UserExporter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Admin\Concerns\BuildsXlsx;
use Tests\TestCase;

class UserImportExportTest extends TestCase
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

        $this->actingAs($user)->get(route('admin.users.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.import'))->assertForbidden();
    }

    public function test_export_never_contains_passwords(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.users.export'));
        $response->assertOk();

        $rows = $this->xlsxRows($response);
        $passwordIndex = array_search('password', $rows[0], true);

        foreach (array_slice($rows, 1) as $row) {
            $this->assertSame('', (string) ($row[$passwordIndex] ?? ''));
        }
    }

    public function test_pdf_export_downloads(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.users.export', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_import_creates_a_verified_user_with_roles_and_a_random_password(): void
    {
        $email = 'import-' . uniqid() . '@example.com';

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            ['Imported User', $email, '0300-0000000', '', 'editor', 1],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['failed'] === 0);

        $user = User::where('email', $email)->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->password);
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_import_updates_without_touching_password_or_roles_when_blank(): void
    {
        $user = User::factory()->create([
            'email' => 'keep-' . uniqid() . '@example.com',
            'password' => 'original-pass-123',
            'is_active' => true,
        ]);
        $user->syncRoles(['editor']);

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            ['New Name', $user->email, '', '', '', ''],
        ]);

        $this->actingAs($this->admin())->post(route('admin.users.import.store'), ['file' => $file]);

        $fresh = $user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertTrue(Hash::check('original-pass-123', $fresh->password));
        $this->assertTrue($fresh->hasRole('editor'));
    }

    public function test_import_sets_a_supplied_password(): void
    {
        $user = User::factory()->create(['email' => 'pw-' . uniqid() . '@example.com', 'is_active' => true]);

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            [$user->name, $user->email, '', 'brand-new-pass-9', '', ''],
        ]);

        $this->actingAs($this->admin())->post(route('admin.users.import.store'), ['file' => $file]);

        $this->assertTrue(Hash::check('brand-new-pass-9', $user->fresh()->password));
    }

    public function test_unknown_role_fails_the_row(): void
    {
        $email = 'role-' . uniqid() . '@example.com';

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            ['Bad Role', $email, '', '', 'not-a-role', 1],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1);

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_import_cannot_deactivate_your_own_account(): void
    {
        $admin = $this->admin();

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            [$admin->name, $admin->email, '', '', '', 0],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1);

        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    public function test_import_cannot_remove_your_own_super_admin_role(): void
    {
        $admin = $this->admin();

        $file = $this->xlsxUpload(UserExporter::HEADERS, [
            [$admin->name, $admin->email, '', '', 'editor', ''],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1);

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }
}
