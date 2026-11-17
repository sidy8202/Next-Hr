<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->default('direct'); $table->string('title', 120)->nullable();
            $table->timestamps(); $table->index(['company_id', 'type', 'updated_at']);
        });
        Schema::create('chat_conversation_participants', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamp('last_read_at')->nullable();
            $table->timestamps(); $table->unique(['conversation_id', 'user_id']); $table->index(['company_id', 'user_id']);
        });
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete(); $table->text('body');
            $table->timestamps(); $table->index(['company_id', 'conversation_id', 'created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('chat_messages'); Schema::dropIfExists('chat_conversation_participants');
        Schema::dropIfExists('chat_conversations');
    }
};
