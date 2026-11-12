<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une paie par mois et par entreprise : brouillon → calculée → clôturée (définitive)
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);                                          // AAAA-MM
            $table->string('status', 12)->default('draft');                       // draft | calculated | closed
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('summary')->nullable();                                  // totaux, écarts, alertes par département, employés non traités
            $table->string('notes', 1000)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period']);
        });

        // Un bulletin par employé et par mois. Il FIGE ses valeurs (snapshot) : une correction passe par une régularisation tracée.
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->string('period', 7);
            $table->string('status', 10)->default('draft');                       // draft | final
            $table->string('employee_name', 150);
            $table->string('matricule', 40)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name', 150)->nullable();
            $table->unsignedBigInteger('gross')->default(0);
            $table->bigInteger('net_to_pay')->default(0);
            $table->bigInteger('net_contract')->default(0);                       // net du contrat : mois complet, sans variables
            $table->unsignedBigInteger('net_known')->nullable();                  // net connu de l'employé (fiche salaire) au moment du calcul
            $table->bigInteger('variables_effect')->default(0);                   // effet des variables, du prorata et des prêts sur le net
            $table->bigInteger('structure_gap')->nullable();                      // net du contrat − net connu
            $table->string('check_status', 14)->default('ok');                    // ok | variables | to_check | no_reference
            $table->unsignedBigInteger('employee_contributions')->default(0);
            $table->unsignedBigInteger('its')->default(0);
            $table->unsignedBigInteger('employer_total')->default(0);
            $table->unsignedBigInteger('employer_cost')->default(0);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_note', 500)->nullable();
            $table->char('input_hash', 32);                                       // empreinte des données du calcul : détecte tout changement avant la clôture
            $table->json('snapshot');                                             // résultat complet, règles légales, salaire et famille utilisés
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['payroll_period_id', 'department_id']);
            $table->index(['employee_id', 'period']);
        });

        Schema::table('payroll_variable_settings', function (Blueprint $table) {
            // Écart toléré (FCFA) entre le net du contrat et le net connu : couvre les arrondis, pas une augmentation oubliée.
            $table->unsignedInteger('net_tolerance')->default(100)->after('loan_effort_alert_percent');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_variable_settings', fn (Blueprint $table) => $table->dropColumn('net_tolerance'));
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_periods');
    }
};
