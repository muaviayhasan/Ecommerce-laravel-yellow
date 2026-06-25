<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curated storefront-placement flags. The home page renders a "Trending" and a
 * "Bestsellers" section (HomeController) that, unlike "On sale" (derived from
 * compare_at_price) or "Featured" (is_featured already exists), have no backing
 * column — admins curate them per product from the add/edit screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->boolean('is_bestseller')->default(false)->after('is_trending');

            $table->index('is_trending');
            $table->index('is_bestseller');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_trending']);
            $table->dropIndex(['is_bestseller']);
            $table->dropColumn(['is_trending', 'is_bestseller']);
        });
    }
};
