<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_variables', function (Blueprint $t) {
            // Vrai quand le RH a modifié la quantité, le taux ou le montant à la main : l'actualisation automatique n'y touche plus.
            $t->boolean('is_edited')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_variables', fn (Blueprint $t) => $t->dropColumn('is_edited'));
    }
};
