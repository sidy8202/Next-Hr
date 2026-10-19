<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('reference', 30);
            $t->string('title', 150);
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $t->string('profile_category', 60)->nullable();
            $t->string('contract_type', 20)->default('CDI');
            $t->text('description')->nullable();
            $t->text('requirements')->nullable();
            $t->string('min_education_level', 20)->nullable();
            $t->decimal('min_experience_years', 4, 1)->default(0);
            $t->unsignedSmallInteger('vacancies')->default(1);
            $t->string('status', 12)->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->date('closes_at')->nullable();
            $t->date('closes_at')->nullable();
            $t->boolean('is_internal')->nullable();
            $t->boolean('is_external')->nullable();
            $t->date('closes_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['company_id', 'reference']);
            $t->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
