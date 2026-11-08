<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réglages propres aux variables du mois et aux prêts (une ligne par entreprise)
        Schema::create('payroll_variable_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pay_basis', 20)->default('base_supplement');          // base | base_supplement : sur quoi se calculent jour et heure
            $table->string('suspension_count_mode', 20)->default('working');      // working (ouvrables) | calendar (calendaires)
            $table->json('rest_weekdays')->nullable();                            // jours non ouvrables (0 = dimanche … 6 = samedi)
            $table->boolean('unjustified_absence_unpaid')->default(true);         // proposer une retenue pour les absences non justifiées
            $table->decimal('loan_effort_alert_percent', 5, 1)->nullable();       // seuil d'alerte du taux d'effort (vide = pas d'alerte)
            $table->timestamps();
        });

        // Taux de majoration des heures supplémentaires de l'entreprise (aucune valeur prérenseignée)
        Schema::create('overtime_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80);
            $table->decimal('percent', 6, 3);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Variables du mois : ce qui change d'un mois à l'autre
        Schema::create('payroll_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->string('period', 7);                                          // AAAA-MM
            $table->string('kind', 20);                                           // overtime_hours | worked_days | unpaid_absence | suspension | bonus | deduction
            $table->foreignId('pay_item_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 10)->nullable();                               // hours | days
            $table->decimal('rate_percent', 6, 3)->nullable();                    // majoration (heures supplémentaires, jours travaillés)
            $table->unsignedBigInteger('amount_override')->nullable();            // montant imposé par le RH
            $table->string('days_mode', 10)->nullable();                          // working | calendar (mises à pied)
            $table->string('source_type', 30)->nullable();                        // absence | leave_request | explanation_case | overtime_record | null = saisie manuelle
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_ref', 255)->nullable();
            $table->string('status', 12)->default('proposed');                    // proposed | confirmed | ignored
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'period', 'status']);
            $table->index(['employee_id', 'period']);
        });

        // Prêts et avances au personnel
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->string('type', 10);                                           // loan | advance
            $table->string('label', 120)->nullable();
            $table->unsignedBigInteger('principal');
            $table->unsignedBigInteger('installment_amount');
            $table->string('first_month', 7);                                     // AAAA-MM de la première échéance
            $table->decimal('interest_rate', 6, 3)->nullable();                   // à titre informatif
            $table->string('status', 12)->default('requested');                   // requested | approved | active | suspended | paid | cancelled
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('disbursed_on')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('employee_id');
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->string('due_month', 7);                                       // AAAA-MM
            $table->unsignedBigInteger('amount');
            $table->string('status', 12)->default('scheduled');                   // scheduled | suspended | deducted | prepaid
            $table->timestamp('deducted_at')->nullable();                         // renseigné à la validation de la paie (lot 14a)
            $table->string('deducted_ref', 60)->nullable();
            $table->timestamps();

            $table->index(['due_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_variables');
        Schema::dropIfExists('overtime_rates');
        Schema::dropIfExists('payroll_variable_settings');
    }
};
