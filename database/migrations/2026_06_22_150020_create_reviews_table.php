<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');                  // 1..5
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_approved')->default(false);         // moderation queue
            $table->boolean('verified_purchase')->default(false);   // from design audit
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'user_id']);              // one review per user per product
            $table->index('product_id');
            $table->index('is_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
