<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identité de l'éditeur (Next HR) imprimée sur les factures : une seule ligne.
        Schema::create('platform_profile', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 150)->default('Next HR');
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 150)->nullable();
            $table->string('rccm', 80)->nullable();
            $table->string('nif', 80)->nullable();
            $table->text('bank_details')->nullable();     // coordonnées de règlement affichées sur la facture et les rappels
            $table->text('tax_note')->nullable();         // mention fiscale libre (TVA, exonération…)
            $table->string('footer_text')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        // Trace de chaque e-mail envoyé (ou en échec) aux entreprises.
        Schema::create('platform_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('type', 20);                   // invoice, reminder_30/7/1, trial_7/3/1, past_due, expired, test
            $table->date('period_end')->nullable();       // échéance concernée : évite d'envoyer deux fois le même rappel
            $table->text('recipients');
            $table->string('subject');
            $table->string('status', 10);                 // sent | failed
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();   // null = envoi automatique
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subscription_id', 'type', 'period_end']);
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->timestamp('invoice_sent_at')->nullable();
            $table->string('invoice_sent_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', fn (Blueprint $t) => $t->dropColumn(['invoice_sent_at', 'invoice_sent_to']));
        Schema::dropIfExists('platform_mail_logs');
        Schema::dropIfExists('platform_profile');
    }
};
