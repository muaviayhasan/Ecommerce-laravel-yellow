<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual discount as a fixed amount or a percentage of the subtotal, on sales
     * (POS / vendor / web) and quotations. `discount_total` (already present) stays
     * the resolved Rs value; these capture how it was entered.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type')->default('fixed')->after('subtotal');      // fixed|percent
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('discount_type')->default('fixed')->after('subtotal');
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
    }
};
