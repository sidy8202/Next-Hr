<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('department_id')->constrained();   // département réel de l'employé
            $table->string('role')->default('member');           // member | chief | driver

            // Perdiem (montants entiers, FCFA sans décimales)
            $table->string('perdiem_mode')->default('to_define'); // manual | daily_salary | to_define
            $table->unsignedSmallInteger('days');                 // jours indemnisables, modifiable par la RH
            $table->unsignedBigInteger('daily_rate')->nullable(); // figé à la validation RH
            $table->unsignedBigInteger('total_amount')->nullable();
            $table->unsignedSmallInteger('salary_divisor')->nullable(); // 30, 26 ou 22 si mode salaire
            $table->timestamp('rate_locked_at')->nullable();

            // Modalité de paiement
            $table->string('payment_mode')->default('after');    // before | tranches | after | custom
            $table->text('payment_note')->nullable();

            $table->timestamps();

            $table->unique(['mission_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_participants');
    }
};
