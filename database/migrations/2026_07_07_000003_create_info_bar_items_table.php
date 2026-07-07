<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_bar_items', function (Blueprint $table) {
            $table->id();
            $table->string('icon');                      // Material Symbols name (e.g. "local_shipping")
            $table->string('title');                     // e.g. "Free Delivery"
            $table->string('subtitle')->nullable();      // e.g. "from Rs 5,000"
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_bar_items');
    }
};
