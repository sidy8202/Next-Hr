<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('work_email')->nullable()->after('personal_email');
            $table->boolean('spouse_works')->nullable()->after('marital_status');
            $table->unsignedTinyInteger('children_count')->default(0)->after('spouse_works');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['work_email', 'spouse_works', 'children_count']);
        });
    }
};
