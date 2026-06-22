<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // Size, Color, Capacity
            $table->string('code')->unique();                        // 'color'
            $table->string('type')->default('select');               // select|swatch|radio
            $table->boolean('is_variation')->default(true);          // creates variants?
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_variation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
