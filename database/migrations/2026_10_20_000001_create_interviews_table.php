<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->dateTime('scheduled_at');
            $t->unsignedSmallInteger('duration_minutes')->default(60);
            $t->string('mode', 10)->default('onsite');   // onsite | phone | video
            $t->string('location', 190)->nullable();     // lieu ou lien de visioconférence
            $t->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 12)->default('scheduled'); // scheduled | done | cancelled | no_show
            $t->text('feedback')->nullable();
            $t->unsignedTinyInteger('score')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['company_id', 'scheduled_at']);
            $t->index(['interviewer_id', 'scheduled_at']);
        });

        Schema::table('candidates', function (Blueprint $t) {
            $t->foreignId('employee_id')->nullable()->after('status')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $t) {
            $t->dropConstrainedForeignId('employee_id');
        });

        Schema::dropIfExists('interviews');
    }
};
