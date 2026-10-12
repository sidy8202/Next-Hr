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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
           $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('matricule', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nationality', 60)->nullable();
            $table->boolean('is_expatriate')->default(false);
            $table->string('marital_status', 20)->nullable();
            $table->string('national_id', 60)->nullable();
            $table->string('work_permit_number')->nullable();
            $table->date('work_permit_expires_at')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('personal_email')->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('position_id')->constrained();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('hire_date');
            $table->string('status', 20)->default('active');
            $table->string('photo_path')->nullable();
            $table->softDeletes();
            $table->unique(['company_id', 'matricule']);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
