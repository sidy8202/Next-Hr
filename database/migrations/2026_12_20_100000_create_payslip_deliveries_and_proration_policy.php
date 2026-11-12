<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suivi d'envoi de chaque bulletin : une ligne par canal (e-mail professionnel, e-mail personnel, application)
        Schema::create('payslip_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->string('channel', 16);                                        // work_email | personal_email | app
            $table->string('address')->nullable();
            $table->string('status', 10)->default('pending');                     // pending | sent | failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->string('error', 500)->nullable();
            $table->char('token', 40)->unique();                                  // jeton du pixel de suivi d'ouverture de l'e-mail
            $table->timestamp('opened_at')->nullable();                           // e-mail ouvert (indicatif)
            $table->timestamp('viewed_at')->nullable();                           // bulletin consulté dans l'application (fiable)
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['payslip_id', 'channel']);
        });

        // Politique de l'entreprise pour un employé qui arrive ou part en cours de mois
        Schema::table('payroll_variable_settings', function (Blueprint $table) {
            $table->string('arrival_rule', 12)->default('threshold')->after('net_tolerance');        // prorate | full | threshold
            $table->unsignedTinyInteger('arrival_grace_days')->default(5)->after('arrival_rule');    // mois entier si arrivée au plus tard le N
            $table->string('departure_rule', 12)->default('prorate')->after('arrival_grace_days');   // prorate | full | threshold
            $table->unsignedTinyInteger('departure_grace_days')->default(5)->after('departure_rule'); // mois entier si départ dans les N derniers jours
        });
    }

    public function down(): void
    {
        Schema::table('payroll_variable_settings', fn (Blueprint $table) => $table->dropColumn(['arrival_rule', 'arrival_grace_days', 'departure_rule', 'departure_grace_days']));
        Schema::dropIfExists('payslip_deliveries');
    }
};
