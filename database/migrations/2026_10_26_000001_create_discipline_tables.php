<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Papier à en-tête de l'entreprise (lettres officielles)
        Schema::create('letterheads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('legal_name', 150);
            $t->string('address', 255)->nullable();
            $t->string('city', 100)->nullable();          // « Bamako » : lieu de la date des lettres
            $t->string('phone', 60)->nullable();
            $t->string('email', 150)->nullable();
            $t->string('website', 150)->nullable();
            $t->string('rccm', 80)->nullable();
            $t->string('nif', 80)->nullable();
            $t->string('logo_path')->nullable();
            $t->string('footer_text', 255)->nullable();
            $t->unsignedTinyInteger('default_response_days')->default(3);
            $t->timestamps();
        });

        // Dossier d'une demande d'explication : lettre, réponse, décision
        Schema::create('explanation_cases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('reference', 30);
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('signatory_name', 150);
            $t->string('signatory_title', 150)->nullable();
            $t->string('subject', 190);
            $t->text('body');
            $t->string('copies', 255)->nullable();
            $t->unsignedTinyInteger('response_days');
            $t->date('response_deadline');
            $t->string('status', 20)->default('awaiting_response'); // awaiting_response | responded | decided | withdrawn
            $t->timestamp('employee_viewed_at')->nullable();       // accusé de réception : première ouverture par l'employé
            $t->timestamp('responded_at')->nullable();
            $t->text('response_body')->nullable();
            $t->string('response_attachment_path')->nullable();
            $t->string('response_attachment_name', 150)->nullable();
            $t->string('decision_type', 16)->nullable();
            $t->string('decision_subject', 190)->nullable();
            $t->text('decision_body')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->date('suspension_start')->nullable();
            $t->date('suspension_end')->nullable();
            $t->boolean('suspension_paid')->default(false);
            $t->date('dismissal_effective_date')->nullable();
            $t->string('withdrawn_reason', 255)->nullable();
            $t->timestamp('reminder_sent_at')->nullable();
            $t->timestamp('overdue_notified_at')->nullable();
            $t->timestamps();

            $t->unique(['company_id', 'reference']);
            $t->index(['company_id', 'status']);
        });

        Schema::create('explanation_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('case_id')->constrained('explanation_cases')->cascadeOnDelete();
            $t->string('kind', 10);                         // request | response | decision
            $t->string('reference', 40);
            $t->string('file_path');
            $t->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('explanation_documents');
        Schema::dropIfExists('explanation_cases');
        Schema::dropIfExists('letterheads');
    }
};
