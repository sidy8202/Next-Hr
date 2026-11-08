<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Règles légales (niveau plateforme) : taux, barèmes, avec date d'effet et statut de validation.
        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->char('country', 2)->default('ML');
            $table->string('code', 30);                           // INPS_SAL, AMO_SAL, ITS, INPS_EMP…
            $table->string('label', 120);
            $table->string('party', 10);                          // employee | employer
            $table->string('kind', 10)->default('rate');          // rate | its
            $table->decimal('rate', 6, 3)->nullable();            // en %
            $table->json('params')->nullable();                   // barème, réduction familiale, réduction de 2 points
            $table->string('base', 12)->default('cotisable');     // cotisable | brut
            $table->boolean('deductible_from_its')->default(false);
            $table->unsignedBigInteger('ceiling_monthly')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 12)->default('to_validate'); // to_validate | validated
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['country', 'code', 'effective_from']);
        });

        // Réglages de paie de l'entreprise.
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->char('country', 2)->default('ML');
            $table->string('convention_name', 150)->nullable();
            $table->unsignedTinyInteger('days_basis')->default(30);          // base de proratisation d'un mois
            $table->decimal('hours_month', 6, 2)->default(173.33);
            $table->string('rounding', 8)->default('nearest');               // nearest | floor | ceil
            $table->boolean('show_employer_part')->default(false);
            $table->timestamps();
        });

        // Grille salariale propre à l'entreprise : catégories (grades) et échelons.
        Schema::create('salary_grids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('convention', 150)->nullable();
            $table->date('effective_from')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_grid_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);                      // E, D, C, B, A, M2, E8B…
            $table->string('label', 120)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('salary_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_grade_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);                     // Échelon 1, Minimum, Médian…
            $table->unsignedBigInteger('amount');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('salary_grade_id')->nullable()->constrained('salary_grades')->nullOnDelete();
        });

        // Salaire attribué à chaque employé, avec historique daté (jamais écrasé).
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('salary_grade_id')->nullable()->constrained('salary_grades')->nullOnDelete();
            $table->foreignId('salary_step_id')->nullable()->constrained('salary_steps')->nullOnDelete();
            $table->string('mode', 12)->default('gross');                    // gross | net_target
            $table->unsignedBigInteger('base_amount')->default(0);           // salaire de base (catégoriel)
            $table->unsignedBigInteger('supplement_amount')->default(0);     // sursalaire
            $table->unsignedBigInteger('target_net')->nullable();            // net convenu (mode net_target)
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
        Schema::table('positions', fn (Blueprint $t) => $t->dropConstrainedForeignId('salary_grade_id'));
        Schema::dropIfExists('salary_steps');
        Schema::dropIfExists('salary_grades');
        Schema::dropIfExists('salary_grids');
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('payroll_rules');
    }
};
