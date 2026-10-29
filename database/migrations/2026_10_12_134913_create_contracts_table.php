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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
             $table->string('type', 20);
            $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->unsignedBigInteger('base_salary');
    $table->string('currency', 3)->default('XOF');
    $table->date('trial_end_date')->nullable();
    $table->unsignedTinyInteger('renewal_number')->default(0);
    $table->foreignId('previous_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
    $table->string('status', 20)->default('active');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['company_id', 'end_date']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
