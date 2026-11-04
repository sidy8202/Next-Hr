<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un abonnement se règle par « cycles » (une période de facturation) en 3 tranches maximum.
     * cycle = numéro de la période réglée ; tranche = rang du paiement dans le cycle ; balance_after = reste à payer après ce paiement.
     */
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle')->default(1);
            $table->unsignedTinyInteger('tranche')->default(1);
            $table->unsignedBigInteger('balance_after')->default(0);
        });

        // Reprise des paiements déjà enregistrés : tout est rangé dans le cycle 1, par ordre d'enregistrement.
        $prices = DB::table('subscriptions')->pluck('price', 'id');

        foreach (DB::table('subscription_payments')->orderBy('id')->get()->groupBy('subscription_id') as $subId => $payments) {
            $paid = 0;

            foreach ($payments->values() as $i => $p) {
                $paid += (int) $p->amount;

                DB::table('subscription_payments')->where('id', $p->id)->update([
                    'cycle' => 1, 'tranche' => min(255, $i + 1), 'balance_after' => max(0, (int) ($prices[$subId] ?? 0) - $paid),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('subscription_payments', fn (Blueprint $t) => $t->dropColumn(['cycle', 'tranche', 'balance_after']));
    }
};
