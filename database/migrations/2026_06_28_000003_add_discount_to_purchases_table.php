<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header-level discount on a purchase: a fixed Rs amount or a percentage of the
     * subtotal. `discount_total` is the resolved money value applied to the grand total.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('discount_type')->default('fixed')->after('subtotal');   // fixed|percent
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type'); // entered amount or %
            $table->decimal('discount_total', 15, 2)->default(0)->after('discount_value'); // resolved Rs discount
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_total']);
        });
    }
};
