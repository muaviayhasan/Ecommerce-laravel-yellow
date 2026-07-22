<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront placement for deals: whether a deal appears in the home-page deal
 * areas, and which single deal is the site-wide spotlight (only one at a time).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(false)->after('is_active')->index();
            $table->boolean('is_spotlight')->default(false)->after('show_on_home')->index();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['show_on_home', 'is_spotlight']);
        });
    }
};
