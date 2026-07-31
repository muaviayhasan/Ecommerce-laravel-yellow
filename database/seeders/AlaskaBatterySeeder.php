<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Alaska Batteries — the seven dry-charged graphite lead-acid models (§4/§5).
 *
 * Standalone and additive: run it with
 *     php artisan db:seed --class=AlaskaBatterySeeder
 *
 * Safe to re-run, and deliberately conservative about what it will overwrite,
 * because it is meant to be usable against the live catalogue:
 *
 *   - Its own SKU namespace (`ALSK-…`). CatalogSeeder numbers its rows
 *     positionally as `SKU-0001`, `SKU-0002`, … so inserting anything into that
 *     sequence would silently rewrite an existing product. Nothing here can
 *     collide with it.
 *   - Descriptive fields (name, specs, highlights, description) are refreshed on
 *     each re-run so spec corrections land, but `published_at` and `is_web_listed`
 *     are carried over from whatever the row already has — re-running never
 *     publishes a product you left in draft, nor unpublishes a live one.
 *   - Variants are create-only. Prices and stock entered in Admin are never
 *     clobbered by a second run.
 *   - A product soft-deleted in Admin is left deleted, not resurrected.
 *
 * Products are seeded as DRAFT (`published_at = null`) with a price of 0,
 * because neither prices nor photographs were supplied. They stay invisible to
 * the storefront until someone sets a real price and publishes them — see
 * Product::scopeWebListed(), which requires a non-null `published_at`.
 *
 * Every hard specification below is transcribed from the manufacturer's own
 * container labels. Nothing is inferred; where a label does not state a value
 * (the two deep-cycle models print no Ah rating) the key is simply absent
 * rather than filled with a guess.
 */
class AlaskaBatterySeeder extends Seeder
{
    /** Keeps these rows clear of CatalogSeeder's positional `SKU-####` sequence. */
    private const SKU_PREFIX = 'ALSK-';

    public function run(): void
    {
        $brand = Brand::updateOrCreate(
            ['slug' => 'alaska'],
            ['name' => 'Alaska', 'is_active' => true],
        );

        // A new root department, sibling to Electronics — batteries are their own
        // family, not an appliance. Sub-categories (UPS / Solar / Automotive) can
        // be added later in Admin → Categories without touching this seeder.
        $category = Category::updateOrCreate(
            ['slug' => 'batteries'],
            ['name' => 'Batteries', 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        );

        $unit = Unit::query()->where('code', 'pcs')->first();

        foreach ($this->models() as $row) {
            $sku = self::SKU_PREFIX . str_replace(' ', '', $row['model']);

            // Respect a deliberate deletion in Admin rather than resurrecting the row
            // (and rather than tripping the unique SKU index with a duplicate).
            $existing = Product::withTrashed()->firstWhere('sku', $sku);
            if ($existing?->trashed()) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'slug' => 'alaska-' . strtolower(str_replace(' ', '-', $row['model'])),
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'unit_id' => $unit?->id,
                    'name' => 'Alaska ' . $row['model'],
                    'type' => Product::TYPE_TRADING,
                    'variant_mode' => Product::VARIANT_SIMPLE,
                    'is_stock_tracked' => true,
                    'is_purchasable' => true,
                    'is_sellable' => true,
                    'short_description' => $row['short_description'],
                    'description' => $row['description'],
                    'highlights' => $row['highlights'],
                    'specifications' => $this->specifications($row),
                    'warranty' => '9 months free replacement warranty',
                    'is_active' => true,
                    // Carried over, never dictated — see the class docblock.
                    'is_web_listed' => $existing->is_web_listed ?? false,
                    'published_at' => $existing->published_at ?? null,
                ],
            );

            // Create-only: a second run must not reset a price or a stock count
            // that has since been entered in Admin.
            if (! $product->variants()->exists()) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "{$sku}-D",
                    'cost' => 0,
                    'retail_price' => 0,   // set in Admin before publishing
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 2,
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }
        }
    }

    /**
     * Spec rows for the product page's Spec tab. Shared plumbing lives here so the
     * per-model table below carries only what actually differs between the seven.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, array<string, string>>
     */
    private function specifications(array $row): array
    {
        $electrical = ['Voltage' => '12 V'];

        // Only the six-figure models print an Ah rating; the deep-cycle pair does not.
        if (isset($row['capacity'])) {
            $electrical['Capacity'] = $row['capacity'];
        }

        $electrical['Plates Per Cell'] = (string) $row['plates'];
        $electrical['Charge State'] = $row['charge_state'];

        return [
            'General' => [
                'Brand' => 'Alaska',
                'Model' => $row['model'],
                'Type' => 'Dry charged lead-acid battery',
                'Technology' => 'Graphite lead-acid',
                'Engineering' => 'German engineering',
            ],
            'Electrical' => $electrical,
            'Physical' => [
                'Container' => 'White polypropylene',
                'Lid & Handle Colour' => $row['lid'],
            ],
            'Warranty & Standards' => [
                'Warranty' => '9 months free replacement',
                'Quality System' => 'ISO 9001:2015',
                'Standards' => 'IS 11466, IS 2277, IS 15124',
            ],
        ];
    }

    /**
     * The seven models, transcribed from the container labels.
     *
     * @return list<array<string, mixed>>
     */
    private function models(): array
    {
        $deepCycleDescription = 'A compact 12-volt deep-cycle battery from Alaska\'s graphite lead-acid range. '
            . 'Deep-cycle construction is built to be discharged and recharged repeatedly, which is what separates '
            . 'it from a starting battery designed for short, heavy bursts. Supplied dry charged, so it is filled '
            . 'with electrolyte and given its first charge at the point of sale and stores without losing condition '
            . 'until then. Carries Alaska\'s 9-month free replacement warranty.';

        $mainsDescription = 'A 12-volt dry-charged graphite lead-acid battery for UPS backup, solar storage and '
            . 'vehicle use. Capacity is quoted at the 20-hour rate, the standard basis for backup and solar sizing. '
            . 'Plate count is the practical measure of how much current a battery of this type can deliver and how '
            . 'long it will hold up under daily cycling — more plates per cell means more active material. Supplied '
            . 'dry charged and commissioned at the point of sale. Carries Alaska\'s 9-month free replacement warranty.';

        return [
            [
                'model' => 'A 55L',
                'plates' => 7,
                'charge_state' => 'Dry & wet charged',
                'lid' => 'Blue',
                'short_description' => '12V deep-cycle graphite lead-acid battery, 7 plates per cell, dry and wet charged.',
                'description' => $deepCycleDescription,
                'highlights' => [
                    '12 V deep-cycle construction',
                    '7 plates per cell',
                    'Graphite lead-acid technology',
                    'Supplied dry and wet charged',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 115',
                'plates' => 5,
                'charge_state' => 'Dry & wet charged',
                'lid' => 'Blue',
                'short_description' => '12V deep-cycle graphite lead-acid battery, 5 plates per cell, dry and wet charged.',
                'description' => $deepCycleDescription,
                'highlights' => [
                    '12 V deep-cycle construction',
                    '5 plates per cell',
                    'Graphite lead-acid technology',
                    'Supplied dry and wet charged',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 130',
                'plates' => 15,
                'capacity' => '100 AH (20HR)',
                'charge_state' => 'Dry charged',
                'lid' => 'Red',
                'short_description' => '12V 100AH dry-charged graphite lead-acid battery with 15 plates per cell, for UPS, solar and vehicle use.',
                'description' => $mainsDescription,
                'highlights' => [
                    '12 V, 100 AH at the 20-hour rate',
                    '15 plates per cell',
                    'Graphite lead-acid technology',
                    'Suits UPS, solar and vehicle use',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 180',
                'plates' => 19,
                'capacity' => '100 AH (20HR)',
                'charge_state' => 'Dry charged',
                'lid' => 'Blue',
                'short_description' => '12V 100AH dry-charged graphite lead-acid battery with 19 plates per cell, for UPS, solar and vehicle use.',
                'description' => $mainsDescription,
                'highlights' => [
                    '12 V, 100 AH at the 20-hour rate',
                    '19 plates per cell',
                    'Graphite lead-acid technology',
                    'Suits UPS, solar and vehicle use',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 200',
                'plates' => 21,
                'capacity' => '105 AH (20HR)',
                'charge_state' => 'Dry charged',
                'lid' => 'Red',
                'short_description' => '12V 105AH dry-charged graphite lead-acid battery with 21 plates per cell, for UPS, solar and vehicle use.',
                'description' => $mainsDescription,
                'highlights' => [
                    '12 V, 105 AH at the 20-hour rate',
                    '21 plates per cell',
                    'Graphite lead-acid technology',
                    'Suits UPS, solar and vehicle use',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 230',
                'plates' => 23,
                'capacity' => '150 AH (20HR)',
                'charge_state' => 'Dry charged',
                'lid' => 'Red',
                'short_description' => '12V 150AH dry-charged graphite lead-acid battery with 23 plates per cell, for UPS, solar and vehicle use.',
                'description' => $mainsDescription,
                'highlights' => [
                    '12 V, 150 AH at the 20-hour rate',
                    '23 plates per cell',
                    'Graphite lead-acid technology',
                    'Suits UPS, solar and vehicle use',
                    '9 months free replacement warranty',
                ],
            ],
            [
                'model' => 'A 270',
                'plates' => 27,
                'capacity' => '180 AH (20HR)',
                'charge_state' => 'Dry charged',
                'lid' => 'Black',
                'short_description' => '12V 180AH dry-charged graphite lead-acid battery with 27 plates per cell, for UPS, solar and vehicle use.',
                'description' => $mainsDescription,
                'highlights' => [
                    '12 V, 180 AH at the 20-hour rate',
                    '27 plates per cell — the largest in the range',
                    'Graphite lead-acid technology',
                    'Suits UPS, solar and vehicle use',
                    '9 months free replacement warranty',
                ],
            ],
        ];
    }
}
