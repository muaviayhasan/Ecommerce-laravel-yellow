<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional delivery info for a purchase: how the supplier's goods arrived
     * (own pickup / courier / person), who handled it, a contact/tracking, and a
     * freight charge that adds to the grand total.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('delivery_method')->nullable()->after('reference');   // pickup|own_rider|courier|other
            $table->string('delivery_agent')->nullable()->after('delivery_method');
            $table->string('delivery_contact')->nullable()->after('delivery_agent');
            $table->decimal('delivery_charge', 15, 2)->default(0)->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'delivery_agent', 'delivery_contact', 'delivery_charge']);
        });
    }
};
