<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deal pricing simplified: items sell at their regular prices and the deal
 * carries one discount (fixed Rs or percent) off the combined total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('discount_type')->default('fixed')->after('bundle_price'); // fixed|percent
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
    }
};
