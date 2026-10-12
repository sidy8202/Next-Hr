<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('nationality', 60)->nullable();
            $table->boolean('is_expatriate')->default(false);
            $table->string('education_level', 30)->nullable();
            $table->string('diploma', 150)->nullable();
            $table->string('field_of_study', 100)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('skills')->nullable();
            $table->string('source', 30)->default('spontaneous');
            $table->string('channel', 30)->nullable();
            $table->date('received_at');
            $table->string('cv_path')->nullable();
            $table->string('status', 20)->default('available');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->date('retain_until')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'email']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'education_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
