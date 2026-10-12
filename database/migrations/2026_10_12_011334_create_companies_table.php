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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 60)->unique();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('timezone', 50)->default('Africa/Bamako');
            $table->string('locale', 5)->default('fr');
            $table->string('currency', 3)->default('XOF');
            $table->string('logo_path')->nullable();
            $table->string('status', 20)->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
