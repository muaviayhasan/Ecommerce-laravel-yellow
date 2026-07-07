<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captured application exceptions, deduplicated by a fingerprint (class + file +
 * line) so a recurring error increments a counter instead of flooding the table.
 * Staff review these in Admin → Error Logs, mark them resolved, and prune them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();     // hash(class|file|line)
            $table->string('level', 20)->default('error');   // error|critical|warning
            $table->string('type');                          // exception class
            $table->text('message');
            $table->string('code', 50)->nullable();
            $table->string('file', 1024)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('method', 10)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->json('context')->nullable();             // sanitized request input
            $table->longText('trace')->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('level');
            // The list scans by "open first, most recent" and prunes by resolution age.
            $table->index(['resolved_at', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
