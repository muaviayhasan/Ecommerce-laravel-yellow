<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ImportExport\Exporters\ProductExporter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Admin\Concerns\BuildsXlsx;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
{
    use BuildsXlsx;
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    /** A row with every column blank, then filled by header name. */
    private function productRow(array $values): array
    {
        $row = array_fill_keys(ProductExporter::HEADERS, '');

        return array_values(array_merge($row, $values));
    }

    private function categorySlug(): string
    {
        return Category::where('is_active', true)->orderBy('id')->value('slug');
    }

    /** A variable product with two variants on a fresh attribute; returns [product, attribute, valueA, valueB]. */
    private function makeVariableProduct(): array
    {
        $uid = uniqid();

        $product = Product::create([
            'name' => "Var Product {$uid}",
            'slug' => "var-product-{$uid}",
            'sku' => "VAR-{$uid}",
            'category_id' => Category::orderBy('id')->value('id'),
            'type' => 'trading',
            'variant_mode' => 'variable',
            'is_active' => true,
        ]);

        $attribute = Attribute::create(['name' => "TestAttr {$uid}", 'code' => "tattr{$uid}", 'type' => 'select', 'is_variation' => true]);
        $valueA = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'alpha', 'label' => 'Alpha']);
        $valueB = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'beta', 'label' => 'Beta']);

        foreach ([[$valueA, true, 100], [$valueB, false, 120]] as [$value, $default, $price]) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => "VAR-{$uid}-" . strtoupper($value->value),
                'retail_price' => $price,
                'stock_quantity' => 5,
                'is_default' => $default,
                'is_active' => true,
            ]);
            $variant->attributeValues()->sync([$value->id]);
        }
        $product->attributes()->sync([$attribute->id]);

        return [$product, $attribute, $valueA, $valueB];
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.products.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.products.import'))->assertForbidden();
    }

    public function test_export_emits_one_row_per_variant(): void
    {
        [$product, $attribute] = $this->makeVariableProduct();

        $rows = $this->xlsxRows(
            $this->actingAs($this->admin())->get(route('admin.products.export', ['search' => $product->sku]))
        );

        $this->assertCount(3, $rows); // header + 2 variants
        $dataRows = array_map(fn ($r) => $this->rowAssoc(ProductExporter::HEADERS, $r), array_slice($rows, 1));

        foreach ($dataRows as $r) {
            $this->assertSame($product->sku, $r['product_sku']);
            $this->assertSame($product->name, $r['name']);
        }
        $this->assertSame(["{$attribute->code}=alpha", "{$attribute->code}=beta"], array_column($dataRows, 'attributes'));
    }

    public function test_pdf_export_downloads(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.products.export', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_import_creates_a_simple_product(): void
    {
        $sku = 'IMP-' . strtoupper(uniqid());

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $sku,
                'name' => "Imported Simple {$sku}",
                'category_slug' => $this->categorySlug(),
                'variant_mode' => 'simple',
                'is_active' => 1,
                'retail_price' => 4500,
                'stock_quantity' => 12,
            ]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['failed'] === 0);

        $product = Product::where('sku', $sku)->firstOrFail();
        $variant = $product->variants()->where('is_default', true)->firstOrFail();
        $this->assertSame(4500.0, (float) $variant->retail_price);
        $this->assertSame(12.0, (float) $variant->stock_quantity);
    }

    public function test_import_creates_a_variable_product_and_auto_creates_attribute_values(): void
    {
        [, $attribute] = $this->makeVariableProduct(); // just for a known attribute code
        $sku = 'IMPV-' . strtoupper(uniqid());

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $sku,
                'name' => "Imported Variable {$sku}",
                'category_slug' => $this->categorySlug(),
                'variant_mode' => 'variable',
                'attributes' => "{$attribute->code}=alpha",
                'retail_price' => 100,
                'variant_is_default' => 1,
            ]),
            $this->productRow([
                'product_sku' => $sku,
                'attributes' => "{$attribute->code}=gamma-" . uniqid(), // value that doesn't exist yet
                'retail_price' => 130,
            ]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['failed'] === 0);

        $product = Product::where('sku', $sku)->firstOrFail();
        $this->assertCount(2, $product->variants);
        $this->assertSame(1, $product->variants()->where('is_default', true)->count());
        $this->assertCount(3, $attribute->fresh()->values); // alpha, beta + auto-created gamma
    }

    public function test_import_updates_price_and_stock_by_sku_and_blank_keeps_values(): void
    {
        [$product] = $this->makeVariableProduct();
        $variant = $product->variants()->where('is_default', true)->first();
        $attributesCell = ProductExporter::attributesCell($variant->load('attributeValues.attribute'));

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $product->sku,
                'variant_sku' => $variant->sku,
                'attributes' => $attributesCell,
                'retail_price' => 999,
                // stock_quantity left blank on purpose — must keep the existing 5
            ]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['updated'] === 1 && $r['failed'] === 0);

        $fresh = $variant->fresh();
        $this->assertSame(999.0, (float) $fresh->retail_price);
        $this->assertSame(5.0, (float) $fresh->stock_quantity);
    }

    public function test_import_never_deletes_variants_missing_from_the_file(): void
    {
        [$product] = $this->makeVariableProduct();
        $this->assertCount(2, $product->variants);
        $variant = $product->variants()->where('is_default', true)->first();
        $attributesCell = ProductExporter::attributesCell($variant->load('attributeValues.attribute'));

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $product->sku,
                'variant_sku' => $variant->sku,
                'attributes' => $attributesCell,
                'retail_price' => 777,
            ]),
        ]);

        $this->actingAs($this->admin())->post(route('admin.products.import.store'), ['file' => $file]);

        $this->assertCount(2, $product->fresh()->variants); // the unlisted variant survived
    }

    public function test_unknown_category_slug_fails_the_product(): void
    {
        $sku = 'IMPX-' . strtoupper(uniqid());

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $sku,
                'name' => 'No category product',
                'category_slug' => 'does-not-exist-' . uniqid(),
                'retail_price' => 10,
            ]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1);

        $this->assertDatabaseMissing('products', ['sku' => $sku]);
    }

    public function test_variant_sku_owned_by_another_product_fails_the_group(): void
    {
        [$productA, $attribute] = $this->makeVariableProduct();
        $stolenSku = $productA->variants()->first()->sku;
        $sku = 'IMPS-' . strtoupper(uniqid());

        $file = $this->xlsxUpload(ProductExporter::HEADERS, [
            $this->productRow([
                'product_sku' => $sku,
                'name' => 'Sku thief',
                'category_slug' => $this->categorySlug(),
                'variant_mode' => 'variable',
                'variant_sku' => $stolenSku,
                'attributes' => "{$attribute->code}=alpha",
                'retail_price' => 10,
            ]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertSessionHas('import_result', fn ($r) => $r['failed'] === 1 && str_contains($r['errors'][0]['message'], 'another product'));

        $this->assertDatabaseMissing('products', ['sku' => $sku]);
    }

    public function test_template_downloads(): void
    {
        $this->actingAs($this->admin())->get(route('admin.products.import.template'))->assertOk();
    }
}
