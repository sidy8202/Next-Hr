<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mode d'affichage de la paie : « simple » (options rares masquées) ou « advanced » (tout visible).
        Schema::table('payroll_variable_settings', function (Blueprint $table) {
            $table->string('ui_mode', 10)->default('simple');
        });

        // Les entreprises qui ont déjà des réglages gardent TOUT visible : rien ne disparaît sous leurs yeux.
        DB::table('payroll_variable_settings')->update(['ui_mode' => 'advanced']);
    }

    public function down(): void
    {
        Schema::table('payroll_variable_settings', fn (Blueprint $table) => $table->dropColumn('ui_mode'));
    }
};
