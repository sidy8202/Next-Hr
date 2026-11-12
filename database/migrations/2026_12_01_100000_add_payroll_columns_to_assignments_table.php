<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $t) {
            // Comment l'affectation se paie : prime fixée (allowance_*, déjà là) ou salaire du poste occupé, proratisé au jour.
            $t->string('pay_mode', 16)->default('allowance')->after('allowance_basis');           // allowance | position_salary
            $t->foreignId('pay_salary_step_id')->nullable()->after('pay_mode')->constrained('salary_steps')->nullOnDelete();
            $t->string('pay_salary_basis', 12)->default('difference')->after('pay_salary_step_id'); // difference (écart avec son salaire) | full (salaire entier du poste)
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pay_salary_step_id');
            $t->dropColumn(['pay_mode', 'pay_salary_basis']);
        });
    }
};
