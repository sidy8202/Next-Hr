<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('step');      // hr | dg
            $table->string('decision');  // approved | rejected | returned
            $table->text('comment')->nullable(); // obligatoire si rejected / returned (validé côté service)
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['mission_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_approvals');
    }
};
