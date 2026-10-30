<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne = une tranche d'un participant : prévue (planned), payée (paid), annulée après paiement (voided)
     * ou abandonnée avec la mission (cancelled). Un paiement n'est jamais modifié ni supprimé : on l'annule avec un motif.
     */
    public function up(): void
    {
        Schema::create('mission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('mission_participants')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('label', 80);

            // Prévu
            $table->unsignedBigInteger('planned_amount');
            $table->date('planned_date')->nullable();
            $table->string('status', 10)->default('planned');   // planned | paid | voided | cancelled

            // Réalisé
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('method', 20)->nullable();            // cash | mobile_money | bank | cheque
            $table->string('reference', 80)->nullable();         // référence de transaction
            $table->text('note')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            // Annulation d'un paiement enregistré
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();

            // Reçu signé (facultatif)
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->char('receipt_sha256', 64)->nullable();

            $table->timestamps();

            $table->index(['participant_id', 'status']);
            $table->index(['status', 'planned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_payments');
    }
};
