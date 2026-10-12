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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('payroll_period_id')->constrained();
            $table->unsignedBigInteger('gross');
            $table->unsignedBigInteger('deductions');
            $table->unsignedBigInteger('net');
            $table->string('currency', 3)->default('XOF');
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'payroll_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
