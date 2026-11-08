<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue de rubriques de l'entreprise
        Schema::create('pay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('label', 120);
            $table->string('type', 20);                                   // gain | gross_deduction | benefit_in_kind | deduction_internal | deduction_individual
            $table->string('system_key', 30)->nullable();                 // rubrique fournie par le moteur (base_salary, supplement, overtime…)
            $table->boolean('is_system')->default(false);
            $table->boolean('subject_to_social')->default(true);          // soumise aux cotisations sociales
            $table->boolean('taxable')->default(true);                    // imposable (base ITS)
            $table->boolean('is_compulsory')->default(false);             // obligatoire (loi, convention) ou facultative
            $table->string('calc_type', 20)->default('fixed');            // system | fixed | percent_base | percent_gross | percent_item | qty_rate | scale
            $table->unsignedBigInteger('default_amount')->nullable();     // montant fixe, ou taux unitaire pour quantité × taux
            $table->decimal('default_rate', 6, 3)->nullable();            // en %
            $table->foreignId('ref_item_id')->nullable()->constrained('pay_items')->nullOnDelete();
            $table->unsignedBigInteger('floor_amount')->nullable();
            $table->unsignedBigInteger('cap_amount')->nullable();
            $table->boolean('show_on_payslip')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // Barème d'une rubrique (ancienneté : à partir de N années, X %)
        Schema::create('pay_item_scale_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('from_years');
            $table->decimal('rate', 6, 3);
            $table->timestamps();

            $table->unique(['pay_item_id', 'from_years']);
        });

        // Modèles de traitement
        Schema::create('pay_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('convention', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pay_profile_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount')->nullable();             // vide = valeur par défaut de la rubrique
            $table->decimal('rate', 6, 3)->nullable();
            $table->timestamps();

            $table->unique(['pay_profile_id', 'pay_item_id']);
        });

        // Affectation datée d'un modèle à un employé (historique jamais écrasé)
        Schema::create('employee_pay_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('pay_profile_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        // Primes et rubriques attribuées à un employé
        Schema::create('employee_pay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('pay_item_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount')->nullable();
            $table->decimal('rate', 6, 3)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'pay_item_id', 'start_date']);
        });

        // Modèles de bulletin (réglages ; le rendu PDF vient au lot 14b)
        Schema::create('payslip_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('convention', 150)->nullable();
            $table->boolean('show_employer_part')->default(false);
            $table->boolean('show_item_codes')->default(true);
            $table->boolean('group_lines')->default(true);
            $table->text('footer_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_templates');
        Schema::dropIfExists('employee_pay_items');
        Schema::dropIfExists('employee_pay_profiles');
        Schema::dropIfExists('pay_profile_items');
        Schema::dropIfExists('pay_profiles');
        Schema::dropIfExists('pay_item_scale_rows');
        Schema::dropIfExists('pay_items');
    }
};
