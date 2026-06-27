<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Foundation seeders run in dependency order:
     * roles/permissions first, then the admin user, then default settings, then a
     * demo catalog (brands, categories, web-listed products + variants) so the
     * storefront has real data.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            UnitSeeder::class,
            CatalogSeeder::class,
        ]);
    }
}
