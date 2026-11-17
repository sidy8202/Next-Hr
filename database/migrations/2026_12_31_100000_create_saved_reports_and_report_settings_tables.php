<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // « Mes rapports » : un rapport de la bibliothèque + ses critères, privé (défaut) ou partagé dans l'entreprise.
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 40);
            $table->string('name', 60);
            $table->json('filters');
            $table->string('visibility', 10)->default('private'); // private | shared
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'visibility', 'report_key']);
        });

        // Seuils de lecture des phrases d'interprétation, par entreprise (les valeurs par défaut sont dans le code).
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->string('value', 40);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
        Schema::dropIfExists('saved_reports');
    }
};
