<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();

            // Styled headline parts — the storefront hero renders these on separate
            // lines (line2 bold, tail + highlight on the last line, highlight coloured).
            $table->string('kicker')->nullable();       // small uppercase label above the heading
            $table->string('line1');                    // first heading line (required)
            $table->string('line2')->nullable();        // second heading line (rendered bold)
            $table->string('tail')->nullable();         // lead-in text before the highlight
            $table->string('highlight')->nullable();    // coloured emphasis (e.g. "30% OFF")

            // Call-to-action button.
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();      // absolute or relative URL; blank → Shop page

            // Image: a library media item (preferred) with a static-path fallback so
            // the bundled theme assets keep working before anything is uploaded.
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('image_path')->nullable();
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
        Schema::dropIfExists('hero_slides');
    }
};
