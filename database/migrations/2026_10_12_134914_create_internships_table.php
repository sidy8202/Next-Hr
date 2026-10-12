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
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
             $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained();
    $table->string('school', 150);
    $table->string('field_of_study')->nullable();
    $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
    $table->boolean('hired_after')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
