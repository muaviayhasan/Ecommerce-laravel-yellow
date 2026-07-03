<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer support chat: one conversation per customer (or guest session),
     * with a stream of messages either from the customer or a staff reply.
     */
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = guest
            $table->string('name');                 // display name (user's name or the guest's typed name)
            $table->string('email')->nullable();
            $table->string('token', 64)->nullable()->unique(); // guest identity (also stored client-side)
            $table->string('status')->default('open');          // open|closed
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_message_at');
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->boolean('from_admin')->default(false);       // true = staff reply
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // sender (if known)
            $table->text('body');
            $table->timestamp('read_at')->nullable();            // when the other side read it
            $table->timestamps();

            $table->index('support_conversation_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
