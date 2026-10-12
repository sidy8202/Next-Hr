<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mission_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40);
            $table->string('purpose');
            $table->text('description')->nullable();
            $table->string('departure_place', 120)->nullable();
            $table->string('destination', 150);
            $table->string('country', 2)->nullable();
            $table->boolean('is_international')->default(false);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('transport_mode', 30)->nullable();
            $table->unsignedBigInteger('estimated_cost')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 20)->default('draft');
            $table->foreignId('requested_by')->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->text('report')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_orders');
    }
};
