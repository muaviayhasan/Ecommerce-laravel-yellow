<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The storefront product page shows a hero "key features" bullet list. Unlike the
 * Specification tab (backed by the existing `specifications` JSON) there was no
 * column for it — admins couldn't set those bullets. Add one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('highlights')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('highlights');
        });
    }
};
