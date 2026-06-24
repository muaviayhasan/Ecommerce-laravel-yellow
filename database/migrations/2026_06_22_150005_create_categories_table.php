<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->decimal('markup_percent', 5, 2)->nullable();     // category-level default markup (pricing)
            $table->boolean('is_active')->default(true);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->foreignId('meta_image_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->timestamps();

            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
