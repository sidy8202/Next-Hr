<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $t) {
            // Rotation « sur site » : le repos (break) remplace en général le congé annuel → pas d'acquisition de congé annuel.
            $t->boolean('grants_annual_leave')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $t) {
            $t->dropColumn('grants_annual_leave');
        });
    }
};
