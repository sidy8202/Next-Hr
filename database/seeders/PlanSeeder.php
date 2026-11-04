<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;

/**
 * Crée les trois formules et leur matrice par défaut. Sans danger si on le relance :
 * ce qui existe déjà (prix, fonctionnalités modifiées dans l'espace plateforme) n'est jamais écrasé.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('plans.plans') as $code => $def) {
            $plan = Plan::firstOrCreate(['code' => $code], $def + ['is_active' => true]);

            foreach (array_keys(config('platform.periods')) as $months) {
                PlanPrice::firstOrCreate(['plan_id' => $plan->id, 'months' => $months], ['price' => null]); // à renseigner dans Formules
            }

            foreach (config('plans.features') as $key => $feature) {
                PlanFeature::firstOrCreate(['plan_id' => $plan->id, 'feature' => $key], ['enabled' => $feature['default'][$code] ?? false]);
            }
        }
    }
}
