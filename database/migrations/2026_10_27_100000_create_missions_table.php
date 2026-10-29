<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();          // OM-2026-0001, attribuée à la soumission
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('department_id')->constrained();  // département demandeur
            $table->string('purpose');
            $table->string('destination_zone');
            $table->string('lodging')->nullable();              // hôtel / quartier, facultatif
            $table->date('start_date');
            $table->date('end_date');
            $table->text('program')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
