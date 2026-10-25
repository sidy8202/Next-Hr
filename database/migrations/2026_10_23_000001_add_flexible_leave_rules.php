<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_settings', function (Blueprint $t) {
            $t->string('count_mode', 24)->default('calendar');         // calendar | calendar_no_holidays | scheduled
            $t->string('accrual_mode', 20)->default('monthly');        // monthly | per_worked_days
            $t->unsignedSmallInteger('accrual_per_days')->default(26); // per_worked_days : « cadence » jours par tranche de N jours travaillés
            $t->json('seniority_bonuses')->nullable();                 // [[années de service, jours en plus par an], …] ; null = valeurs de config/leaves.php
        });

        // Plannings : semaine standard ou rotation (ex. 14 jours de travail / 14 jours de repos)
        Schema::create('work_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('name', 80);
            $t->string('type', 10)->default('standard');               // standard | rotation
            $t->json('workdays')->nullable();                          // standard : 1 = lundi … 7 = dimanche
            $t->unsignedSmallInteger('on_days')->nullable();           // rotation : jours de travail
            $t->unsignedSmallInteger('off_days')->nullable();          // rotation : jours de repos
            $t->date('anchor_date')->nullable();                       // rotation : premier jour d'une période de travail
            $t->timestamps();
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete(); // null = semaine standard de l'entreprise
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('work_schedule_id');
        });
        Schema::dropIfExists('work_schedules');
        Schema::table('leave_settings', function (Blueprint $t) {
            $t->dropColumn(['count_mode', 'accrual_mode', 'accrual_per_days', 'seniority_bonuses']);
        });
    }
};
