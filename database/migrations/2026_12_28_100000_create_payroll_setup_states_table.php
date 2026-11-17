<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // État de l'assistant « Démarrer la paie » : une ligne par entreprise.
        // La progression elle-même n'est JAMAIS stockée : elle est calculée en direct à partir des données.
        Schema::create('payroll_setup_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('is_hidden')->default(false);              // assistant masqué (peut être rouvert)
            $table->json('confirmed')->nullable();                     // étapes confirmées à la main : ['settings', 'variable_settings']
            $table->json('skipped')->nullable();                       // étapes facultatives ignorées : ['grid', 'template']
            $table->timestamp('welcomed_at')->nullable();              // message d'accueil déjà vu
            $table->timestamp('simulation_done_at')->nullable();       // première simulation de contrôle lancée
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_setup_states');
    }
};
