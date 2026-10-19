<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('first_name', 100);
            $t->string('last_name', 100);
            $t->string('email', 190)->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('city', 100)->nullable();
            $t->string('education_level', 20)->default('none');
            $t->string('last_degree', 150)->nullable();
            $t->string('degree_field', 150)->nullable();
            $t->string('school', 150)->nullable();
            $t->unsignedSmallInteger('graduation_year')->nullable();
            $t->decimal('experience_years', 4, 1)->default(0);
            $t->string('current_job_title', 150)->nullable();
            $t->string('profile_category', 60)->nullable();
            $t->json('skills')->nullable();
            $t->string('source', 30)->nullable();
            $t->string('cv_path')->nullable();
            $t->string('cv_original_name', 150)->nullable();
            $t->string('status', 12)->default('active');
            $t->text('notes')->nullable();
            $t->timestamp('consent_given_at')->nullable();
            $t->date('retention_until')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['company_id', 'email']);
            $t->index(['company_id', 'profile_category']);
            $t->index(['company_id', 'education_level']);
            $t->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
