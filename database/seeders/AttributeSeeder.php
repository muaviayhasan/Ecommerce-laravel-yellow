<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

/**
 * Seeds the variation attributes the catalogue needs: Color (a swatch attribute
 * carrying hex codes), Size (a plain select), and Capacity in litres (a select
 * that drives water-cooler, gas-geyser, water-dispenser and air-cooler variants).
 * All are marked `is_variation` so they can drive product variants.
 *
 * Idempotent: attributes match on `code` and values on `attribute_id` + `value`,
 * so re-seeding refreshes labels/hex/order without creating duplicates.
 */
class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Color — swatch attribute. [value slug, label, hex]
        $colors = [
            ['white', 'White', '#FFFFFF'],
            ['black', 'Black', '#000000'],
            ['gray', 'Gray', '#6B7280'],
            ['silver', 'Silver', '#C0C0C0'],
            ['red', 'Red', '#EF4444'],
            ['maroon', 'Maroon', '#7F1D1D'],
            ['orange', 'Orange', '#F97316'],
            ['yellow', 'Yellow', '#FACC15'],
            ['green', 'Green', '#22C55E'],
            ['blue', 'Blue', '#3B82F6'],
            ['navy', 'Navy', '#1E3A8A'],
            ['purple', 'Purple', '#A855F7'],
            ['pink', 'Pink', '#EC4899'],
            ['brown', 'Brown', '#92400E'],
        ];

        $this->seedAttribute(
            ['name' => 'Color', 'code' => 'color', 'type' => 'swatch', 'sort_order' => 0],
            collect($colors)->map(fn ($c, $i) => [
                'value' => $c[0], 'label' => $c[1], 'color_hex' => $c[2], 'sort_order' => $i,
            ])->all(),
        );

        // Size — select attribute (no hex). [value slug, label]
        $sizes = [
            ['xs', 'Extra Small'],
            ['s', 'Small'],
            ['m', 'Medium'],
            ['r', 'Regular'],
            ['l', 'Large'],
            ['xl', 'Extra Large'],
        ];

        $this->seedAttribute(
            ['name' => 'Size', 'code' => 'size', 'type' => 'select', 'sort_order' => 1],
            collect($sizes)->map(fn ($s, $i) => [
                'value' => $s[0], 'label' => $s[1], 'color_hex' => null, 'sort_order' => $i,
            ])->all(),
        );

        // Capacity — select attribute in litres. Drives variants for water
        // coolers, instant/storage gas geysers, water dispensers and air coolers.
        // Range spans the small storage geysers (6–15 L) through air-cooler tanks
        // (15–70 L) up to the larger steel water coolers (45–100 L). Values are
        // whole litres; the slug is "<n>l" and the label "<n> Liters".
        $capacities = [6, 8, 10, 12, 15, 20, 24, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 85, 100];

        $this->seedAttribute(
            ['name' => 'Capacity', 'code' => 'capacity', 'type' => 'select', 'sort_order' => 2],
            collect($capacities)->map(fn ($litres, $i) => [
                'value' => $litres.'l', 'label' => $litres.' Liters', 'color_hex' => null, 'sort_order' => $i,
            ])->all(),
        );
    }

    /**
     * Upsert an attribute and its values.
     *
     * @param  array{name:string, code:string, type:string, sort_order:int}  $attributeData
     * @param  list<array{value:string, label:string, color_hex:?string, sort_order:int}>  $values
     */
    private function seedAttribute(array $attributeData, array $values): void
    {
        $attribute = Attribute::updateOrCreate(
            ['code' => $attributeData['code']],
            [
                'name' => $attributeData['name'],
                'type' => $attributeData['type'],
                'is_variation' => true,
                'sort_order' => $attributeData['sort_order'],
            ],
        );

        foreach ($values as $value) {
            AttributeValue::updateOrCreate(
                ['attribute_id' => $attribute->id, 'value' => $value['value']],
                [
                    'label' => $value['label'],
                    'color_hex' => $value['color_hex'],
                    'sort_order' => $value['sort_order'],
                ],
            );
        }
    }
}
