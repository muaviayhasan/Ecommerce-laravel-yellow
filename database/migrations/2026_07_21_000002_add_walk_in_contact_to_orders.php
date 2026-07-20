<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional walk-in contact details captured at the POS counter, so the printed
 * bill can carry the actual person's name and number instead of "Walk-in".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('walk_in_name', 100)->nullable()->after('customer_id');
            $table->string('walk_in_phone', 30)->nullable()->after('walk_in_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['walk_in_name', 'walk_in_phone']);
        });
    }
};
