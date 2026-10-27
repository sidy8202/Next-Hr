<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name', 100);
            $t->string('kind', 8)->default('days');                    // days | hours
            $t->decimal('free_days', 4, 1)->default(2);                // jours accordés sans retenue, par demande
            $t->string('excess_policy', 14)->default('deduct_leave');  // deduct_leave | refuse | unpaid
            $t->decimal('max_days', 4, 1)->nullable();                 // durée maximale par demande
            $t->decimal('yearly_cap_days', 5, 1)->nullable();          // plafond annuel (jours) pour ce type
            $t->boolean('requires_document')->default(false);
            $t->decimal('hours_free_per_month', 5, 1)->nullable();     // type « heures » : quota mensuel gratuit
            $t->text('sheet_message')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedTinyInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('permission_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_type_id')->constrained('permission_types');
            $t->date('start_date');
            $t->date('end_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->decimal('days', 5, 2);                                 // durée en jours (heures ÷ heures par jour)
            $t->decimal('hours', 5, 2)->nullable();
            $t->decimal('free_days', 5, 2)->default(0);
            $t->decimal('deducted_days', 5, 2)->default(0);            // retirés du congé annuel
            $t->decimal('unpaid_days', 5, 2)->default(0);              // jours non payés (pour la paie)
            $t->text('reason')->nullable();
            $t->text('exception_reason')->nullable();
            $t->boolean('exceeds_balance')->default(false);
            $t->decimal('balance_before', 6, 2)->nullable();
            $t->decimal('balance_after', 6, 2)->nullable();
            $t->string('attachment_path')->nullable();
            $t->string('attachment_name', 150)->nullable();
            $t->string('status', 12)->default('pending');
            $t->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['company_id', 'status']);
            $t->index(['employee_id', 'start_date', 'end_date']);
        });

        // Bases / sites de travail (Bamako, Sikasso, Ségou…)
        Schema::create('work_sites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('name', 100);
            $t->string('city', 100)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'name']);
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('work_site_id')->nullable()->constrained('work_sites')->nullOnDelete(); // base principale
        });

        // Affectations : poste occupé EN PLUS (ou à la place) du poste principal, dans n'importe quel département ou base.
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('type', 12);                                    // interim | secondment | additional
            $t->foreignId('department_id')->constrained('departments');
            $t->foreignId('position_id')->constrained('positions');
            $t->foreignId('work_site_id')->nullable()->constrained('work_sites')->nullOnDelete();
            $t->foreignId('reports_to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->unsignedTinyInteger('allocation_percent')->nullable(); // part du temps / du coût (ex. 40)
            $t->decimal('allowance_amount', 12, 2)->nullable();        // prime liée à l'affectation
            $t->string('allowance_basis', 8)->default('monthly');      // monthly (proratisée au jour) | total (répartie sur la durée)
            $t->boolean('acts_as_head')->default(false);               // fait fonction de chef du département : valide à sa place
            $t->boolean('manager_validates')->default(true);           // le supérieur de l'affectation valide aussi les congés de l'employé
            $t->string('reason', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['employee_id', 'start_date', 'end_date']);
            $t->index(['company_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::table('employees', fn (Blueprint $t) => $t->dropConstrainedForeignId('work_site_id'));
        Schema::dropIfExists('work_sites');
        Schema::dropIfExists('permission_requests');
        Schema::dropIfExists('permission_types');
    }
};
