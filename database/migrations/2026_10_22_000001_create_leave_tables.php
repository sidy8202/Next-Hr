<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tables des congés. Les tables « leave_types », « leave_requests » et « holidays » EXISTENT déjà (première version de
 * l'application) : on leur AJOUTE seulement les colonnes manquantes. Chaque étape vérifie l'état avant d'agir : la migration
 * peut être relancée sans erreur, même après un échec partiel.
 */
return new class extends Migration
{
    private function column(string $table, string $column, Closure $define): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, fn (Blueprint $t) => $define($t));
        }
    }

    public function up(): void
    {
        if (! Schema::hasTable('leave_settings')) {
            Schema::create('leave_settings', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
                $t->decimal('negative_tolerance', 4, 1)->default(5);
                $t->decimal('default_monthly_days', 4, 2)->default(2.5);
                $t->json('workdays')->nullable();
                $t->boolean('deduct_unjustified')->default(true);
                $t->unsignedSmallInteger('recovery_validity_days')->default(90);
                $t->timestamps();
            });
        }

        // ---- leave_types (existante : name, days_per_year, is_paid, requires_approval, is_active) ----
        $firstTime = ! Schema::hasColumn('leave_types', 'code');

        $this->column('leave_types', 'code', fn ($t) => $t->string('code', 30)->nullable()); // nullable : les anciens types n'ont pas de code
        $this->column('leave_types', 'pool', fn ($t) => $t->string('pool', 10)->nullable());
        $this->column('leave_types', 'requires_document', fn ($t) => $t->boolean('requires_document')->default(false));
        $this->column('leave_types', 'max_days', fn ($t) => $t->decimal('max_days', 4, 1)->nullable());
        $this->column('leave_types', 'paid', fn ($t) => $t->boolean('paid')->default(true));
        $this->column('leave_types', 'active', fn ($t) => $t->boolean('active')->default(true));
        $this->column('leave_types', 'sort', fn ($t) => $t->unsignedTinyInteger('sort')->default(0));

        if (! Schema::hasIndex('leave_types', 'leave_types_company_id_code_unique')) {
            Schema::table('leave_types', fn (Blueprint $t) => $t->unique(['company_id', 'code']));
        }

        if ($firstTime) {
            // Anciens types (première version) : désactivés ; les types du nouveau module sont créés automatiquement.
            DB::table('leave_types')->update(['active' => false]);
        }

        // ---- leave_requests (existante : days, status, approved_by, decided_at, decision_note…) ----
        $this->column('leave_requests', 'start_half', fn ($t) => $t->boolean('start_half')->default(false));
        $this->column('leave_requests', 'end_half', fn ($t) => $t->boolean('end_half')->default(false));
        $this->column('leave_requests', 'exception_reason', fn ($t) => $t->text('exception_reason')->nullable());
        $this->column('leave_requests', 'exceeds_balance', fn ($t) => $t->boolean('exceeds_balance')->default(false));
        $this->column('leave_requests', 'balance_before', fn ($t) => $t->decimal('balance_before', 6, 2)->nullable());
        $this->column('leave_requests', 'balance_after', fn ($t) => $t->decimal('balance_after', 6, 2)->nullable());
        $this->column('leave_requests', 'interim_employee_id', fn ($t) => $t->foreignId('interim_employee_id')->nullable()->constrained('employees')->nullOnDelete());
        $this->column('leave_requests', 'attachment_path', fn ($t) => $t->string('attachment_path')->nullable());
        $this->column('leave_requests', 'attachment_name', fn ($t) => $t->string('attachment_name', 150)->nullable());
        $this->column('leave_requests', 'document_id', fn ($t) => $t->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete());
        $this->column('leave_requests', 'created_by', fn ($t) => $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete());

        if (! Schema::hasIndex('leave_requests', 'leave_requests_employee_id_start_date_end_date_index')) {
            Schema::table('leave_requests', fn (Blueprint $t) => $t->index(['employee_id', 'start_date', 'end_date']));
        }

        // ---- Nouvelles tables ----
        if (! Schema::hasTable('approval_steps')) {
            Schema::create('approval_steps', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->string('approvable_type', 20);             // leave | overtime | permission
                $t->unsignedBigInteger('approvable_id');
                $t->unsignedTinyInteger('position');
                $t->string('kind', 10);                        // manager | hr
                $t->string('status', 12)->default('pending');  // pending | approved | rejected | skipped | cancelled
                $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $t->string('acted_as', 14)->nullable();        // manager | delegate | hr | hr_override
                $t->text('comment')->nullable();
                $t->timestamp('decided_at')->nullable();
                $t->timestamps();

                $t->index(['approvable_type', 'approvable_id']);
                $t->index(['company_id', 'status']);
            });
        }

        // Registre des jours de congé : le solde est TOUJOURS la somme des écritures (traçable, jamais écrasé).
        if (! Schema::hasTable('leave_ledger')) {
            Schema::create('leave_ledger', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $t->string('pool', 10);                        // annual | recovery
                $t->string('type', 24);                        // accrual | taken | refund | unjustified_absence | recovery_credit | adjustment | expiry
                $t->decimal('days', 6, 2);                     // + crédit, − débit
                $t->date('effective_date');
                $t->date('expires_on')->nullable();
                $t->string('reference_type', 20)->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->string('period_key', 24)->nullable();      // idempotence des acquisitions mensuelles
                $t->string('note', 255)->nullable();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('created_at')->nullable();

                $t->unique(['employee_id', 'type', 'period_key']);
                $t->index(['employee_id', 'pool']);
            });
        }

        if (! Schema::hasTable('absences')) {
            Schema::create('absences', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $t->date('start_date');
                $t->date('end_date');
                $t->decimal('days', 4, 1);
                $t->boolean('justified')->default(false);
                $t->string('reason', 255)->nullable();
                $t->decimal('deducted_days', 4, 1)->default(0);
                $t->timestamp('justified_at')->nullable();
                $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('overtime_records')) {
            Schema::create('overtime_records', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $t->date('worked_on');
                $t->decimal('days', 3, 2);
                $t->string('reason', 255)->nullable();
                $t->string('status', 12)->default('pending');  // pending | approved | rejected | cancelled
                $t->string('compensation', 14)->nullable();    // recovery | leave_balance
                $t->decimal('credited_days', 4, 2)->nullable();
                $t->date('expires_on')->nullable();
                $t->timestamp('decided_at')->nullable();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();

                $t->unique(['employee_id', 'worked_on']);
            });
        }

        // ---- employees : cadence de congé et date de début d'acquisition ----
        $firstEmployees = ! Schema::hasColumn('employees', 'leave_accrual_from');
        $this->column('employees', 'leave_days_per_month', fn ($t) => $t->decimal('leave_days_per_month', 4, 2)->nullable());
        $this->column('employees', 'leave_accrual_from', fn ($t) => $t->date('leave_accrual_from')->nullable());

        if ($firstEmployees) {
            // Employés existants : l'acquisition démarre à l'installation (la RH saisit le solde initial par un ajustement).
            DB::table('employees')->update(['leave_accrual_from' => today()->toDateString()]);
        }
    }

    public function down(): void
    {
        foreach (['overtime_records', 'absences', 'leave_ledger', 'approval_steps', 'leave_settings'] as $table) {
            Schema::dropIfExists($table);
        }
        // Les colonnes ajoutées aux tables existantes (leave_types, leave_requests, employees) sont conservées.
    }
};
