<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional map coordinates for a saved address — captured from Google Places /
     * "use my location" / the draggable pin on the storefront address form, so riders
     * can navigate to the exact spot when the typed address is fuzzy.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('country');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
