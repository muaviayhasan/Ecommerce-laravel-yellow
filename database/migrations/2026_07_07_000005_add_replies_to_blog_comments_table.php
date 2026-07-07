<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            // Threaded replies: a reply points at its parent comment. Admin replies
            // are flagged so the storefront can badge them as staff answers.
            $table->foreignId('parent_id')->nullable()->after('post_id')->constrained('blog_comments')->cascadeOnDelete();
            $table->boolean('is_admin')->default(false)->after('is_approved');

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_admin']);
        });
    }
};
