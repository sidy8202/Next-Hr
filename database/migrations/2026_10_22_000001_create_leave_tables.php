<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('holidays', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->string('name', 120);
            $t->timestamps();
            $t->unique(['company_id', 'date']);
        });

        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name', 100);
            $t->string('pool', 10)->nullable();            // annual | recovery | null
            $t->boolean('requires_document')->default(false);
            $t->decimal('max_days', 4, 1)->nullable();
            $t->boolean('paid')->default(true);
            $t->boolean('active')->default(true);
            $t->unsignedTinyInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types');
            $t->date('start_date');
            $t->date('end_date');
            $t->boolean('start_half')->default(false);   // commence l'après-midi
            $t->boolean('end_half')->default(false);     // termine à midi
            $t->decimal('days', 4, 1);
            $t->text('reason')->nullable();
            $t->text('exception_reason')->nullable();
            $t->boolean('exceeds_balance')->default(false);
            $t->decimal('balance_before', 6, 2)->nullable();
            $t->decimal('balance_after', 6, 2)->nullable();
            $t->foreignId('interim_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('attachment_path')->nullable();
            $t->string('attachment_name', 150)->nullable();
            $t->string('status', 12)->default('pending');  // pending | approved | rejected | cancelled
            $t->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['company_id', 'status']);
            $t->index(['employee_id', 'start_date', 'end_date']);
        });

        Schema::create('approval_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('approvable_type', 20);             // leave | overtime | …
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

        // Registre des jours de congé : le solde est TOUJOURS la somme des écritures (traçable, jamais écrasé).
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

        Schema::table('employees', function (Blueprint $t) {
            $t->decimal('leave_days_per_month', 4, 2)->nullable();
            $t->date('leave_accrual_from')->nullable();
        });

        // Employés existants : l'acquisition démarre à l'installation (la RH saisit le solde initial par un ajustement).
        DB::table('employees')->update(['leave_accrual_from' => today()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropColumn(['leave_days_per_month', 'leave_accrual_from']);
        });

        foreach (['overtime_records', 'absences', 'leave_ledger', 'approval_steps', 'leave_requests', 'leave_types', 'holidays', 'leave_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
