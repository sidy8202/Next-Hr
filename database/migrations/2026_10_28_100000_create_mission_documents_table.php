<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('mission_participants')->cascadeOnDelete();
            $table->string('kind', 10);                       // order (généré) | signed (scan signé)
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('reference', 40);                  // OM-2026-0001-02
            $table->string('file_path');                      // disque local, jamais public
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->char('sha256', 64);                       // empreinte : détecte toute modification du fichier archivé
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();   // remplacé par une version plus récente
            $table->timestamp('voided_at')->nullable();       // mission annulée
            $table->timestamps();

            $table->index(['participant_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_documents');
    }
};
