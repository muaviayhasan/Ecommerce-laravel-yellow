<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use App\Services\ImportExport\Exporters\CategoryExporter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Admin\Concerns\BuildsXlsx;
use Tests\TestCase;

class CategoryImportExportTest extends TestCase
{
    use BuildsXlsx;
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.categories.export'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.categories.import'))->assertRedirect(route('admin.login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.categories.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.categories.import'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.categories.import.template'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.categories.import.store'))->assertForbidden();
    }

    public function test_export_downloads_an_xlsx_with_all_columns(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.categories.export'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('categories-', (string) $response->headers->get('content-disposition'));

        $rows = $this->xlsxRows($response);
        $this->assertSame(CategoryExporter::HEADERS, array_map('strval', array_slice($rows[0], 0, count(CategoryExporter::HEADERS))));
        $this->assertGreaterThan(1, count($rows)); // seeded catalog has categories
    }

    public function test_export_respects_the_status_filter(): void
    {
        $inactive = Category::create(['name' => 'Off ' . uniqid(), 'slug' => 'off-' . uniqid(), 'is_active' => false]);

        $rows = $this->xlsxRows(
            $this->actingAs($this->admin())->get(route('admin.categories.export', ['status' => 'active']))
        );

        $slugs = array_column(array_slice($rows, 1), 1); // slug column
        $this->assertNotContains($inactive->slug, $slugs);
    }

    public function test_pdf_export_downloads(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.categories.export', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_template_downloads(): void
    {
        $this->actingAs($this->admin())->get(route('admin.categories.import.template'))->assertOk();
    }

    public function test_import_creates_and_updates_by_slug(): void
    {
        $existing = Category::create(['name' => 'Old name', 'slug' => 'imp-' . uniqid(), 'is_active' => true]);
        $newSlug = 'imp-new-' . uniqid();

        $file = $this->xlsxUpload(CategoryExporter::HEADERS, [
            ['Renamed by import', $existing->slug, '', '', 5, '', '', '', '', ''],
            ['Brand new category', $newSlug, '', 'Made by a test', 7, '9.5', 1, '', '', ''],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.import.store'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['updated'] === 1 && $r['failed'] === 0);

        $this->assertSame('Renamed by import', $existing->fresh()->name);
        $this->assertSame(5, $existing->fresh()->sort_order);
        $this->assertDatabaseHas('categories', ['slug' => $newSlug, 'name' => 'Brand new category', 'sort_order' => 7]);
    }

    public function test_blank_cells_keep_existing_values(): void
    {
        $existing = Category::create([
            'name' => 'Keeps data', 'slug' => 'keep-' . uniqid(),
            'description' => 'Original description', 'is_active' => false, 'sort_order' => 9,
        ]);

        $file = $this->xlsxUpload(CategoryExporter::HEADERS, [
            ['Keeps data', $existing->slug, '', '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin())->post(route('admin.categories.import.store'), ['file' => $file]);

        $fresh = $existing->fresh();
        $this->assertSame('Original description', $fresh->description);
        $this->assertFalse($fresh->is_active);
        $this->assertSame(9, $fresh->sort_order);
    }

    public function test_parent_defined_later_in_the_file_is_resolved(): void
    {
        $childSlug = 'child-' . uniqid();
        $parentSlug = 'parent-' . uniqid();

        $file = $this->xlsxUpload(CategoryExporter::HEADERS, [
            ['Child first', $childSlug, $parentSlug, '', '', '', 1, '', '', ''],
            ['Parent later', $parentSlug, '', '', '', '', 1, '', '', ''],
        ]);

        $this->actingAs($this->admin())->post(route('admin.categories.import.store'), ['file' => $file]);

        $child = Category::where('slug', $childSlug)->firstOrFail();
        $parent = Category::where('slug', $parentSlug)->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_a_bad_row_does_not_abort_the_batch(): void
    {
        $goodSlug = 'good-' . uniqid();

        $file = $this->xlsxUpload(CategoryExporter::HEADERS, [
            ['', 'bad-' . uniqid(), '', '', '', '', '', '', '', ''], // no name
            ['Good row', $goodSlug, '', '', '', '', 1, '', '', ''],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['failed'] === 1 && $r['errors'] !== []);

        $this->assertDatabaseHas('categories', ['slug' => $goodSlug]);
    }

    public function test_non_xlsx_uploads_are_rejected(): void
    {
        $file = UploadedFile::fake()->create('data.csv', 5, 'text/csv');

        $this->actingAs($this->admin())
            ->post(route('admin.categories.import.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_wrong_header_row_is_rejected_with_a_clear_error(): void
    {
        $file = $this->xlsxUpload(['totally', 'wrong', 'headers'], [['a', 'b', 'c']]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.import.store'), ['file' => $file])
            ->assertSessionHas('error');
    }
}
