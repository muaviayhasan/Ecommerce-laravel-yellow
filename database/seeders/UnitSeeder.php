<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Seed the default units of measure. Idempotent: matches on `code`, so
     * re-seeding only adds the ones that don't exist yet (and refreshes names).
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Pieces', 'code' => 'pcs'],
            ['name' => 'Kilogram', 'code' => 'kg'],
            ['name' => 'Gram', 'code' => 'g'],
            ['name' => 'Litre', 'code' => 'ltr'],
            ['name' => 'Millilitre', 'code' => 'ml'],
            ['name' => 'Dozen', 'code' => 'dozen'],
            ['name' => 'Box', 'code' => 'box'],
            ['name' => 'Carton', 'code' => 'carton'],
            ['name' => 'Pack', 'code' => 'pack'],
            ['name' => 'Bottle', 'code' => 'bottle'],
            ['name' => 'Can', 'code' => 'can'],
            ['name' => 'Bag', 'code' => 'bag'],
            ['name' => 'Tray', 'code' => 'tray'],
            ['name' => 'Pound', 'code' => 'lb'],
            ['name' => 'Ounce', 'code' => 'oz'],
        ];

        foreach ($units as $i => $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                ['name' => $unit['name'], 'sort_order' => $i, 'is_active' => true],
            );
        }
    }
}
