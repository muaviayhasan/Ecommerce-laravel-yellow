<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_cards', function (Blueprint $table) {
            $table->id();

            $table->string('kicker')->nullable();       // small uppercase label (e.g. "Catch the hottest")
            $table->string('title');                     // main heading (e.g. "Deals")
            $table->string('subtitle')->nullable();      // optional line under the title (e.g. "In Cameras")

            // Bottom-row style: shop → "Shop now", price → prefix+currency+amount+cents, percent → prefix+amount%
            $table->string('display_type')->default('shop');
            $table->string('prefix')->nullable();        // "From" / "Up to"
            $table->string('currency')->nullable();      // "$" (price only)
            $table->string('amount')->nullable();        // "749" / "70"
            $table->string('cents')->nullable();         // "99" (price only)

            $table->string('url')->nullable();           // link target; blank → Shop page

            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('image_path')->nullable();    // static-asset fallback
            $table->string('image_alt')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_cards');
    }
};
