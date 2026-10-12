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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable()->index()->after('company_id');
            $table->boolean('is_platform_admin')->default(false);
            $table->boolean('is_company_owner')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_login_at')->nullable();
            $table->boolean('must_change_password')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'employee_id',
                'is_platform_admin',
                'is_company_owner',
                'is_active',
                'first_login_at',
                'must_change_password',
            ]);
        });
    }
};
