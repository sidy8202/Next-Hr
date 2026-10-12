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
        Schema::create('mission_order_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->unsignedBigInteger('per_diem')->default(0);
            $table->unsignedBigInteger('advance_amount')->default(0);
            $table->unsignedBigInteger('actual_expenses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['mission_order_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_order_employees');
    }
};
