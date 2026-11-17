<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paquets par secteur : maintenus par la plateforme, datés, versionnés, validés par un comptable (comme payroll_rules).
        Schema::create('payroll_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40);                                   // commerce, transit, banques…
            $table->string('name', 150);
            $table->string('sector', 80)->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 12)->default('to_validate');         // to_validate | validated
            $table->text('description')->nullable();
            $table->string('source', 255)->nullable();                    // d'où vient le contenu (bulletin, convention, comptable)
            $table->json('payload');                                      // items, profiles, grids, template, settings
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['code', 'version']);
        });

        // Historique : quelle entreprise a appliqué quel paquet (version et état de validation au moment du clic).
        Schema::create('payroll_package_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_package_id')->constrained()->restrictOnDelete();
            $table->string('package_code', 40);
            $table->string('package_name', 150);
            $table->unsignedSmallInteger('package_version');
            $table->boolean('was_validated')->default(false);
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('report');                                       // ce qui a été créé / ignoré
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_package_applications');
        Schema::dropIfExists('payroll_packages');
    }
};
