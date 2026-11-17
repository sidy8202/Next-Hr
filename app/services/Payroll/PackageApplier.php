<?php

namespace App\Services\Payroll;

use App\Models\PayItem;
use App\Models\PayItemScaleRow;
use App\Models\PayProfile;
use App\Models\PayProfileItem;
use App\Models\PayrollPackage;
use App\Models\PayrollPackageApplication;
use App\Models\PayrollSetting;
use App\Models\PayslipTemplate;
use App\Models\SalaryGrade;
use App\Models\SalaryGrid;
use App\Models\SalaryStep;
use Illuminate\Support\Facades\DB;

/**
 * Application d'un paquet à une entreprise, en base. Toute la décision (créer / ignorer) vient de PackagePlanner (pur, testé) :
 * ici on ne fait qu'exécuter le plan, dans une transaction. Rien n'est jamais écrasé ni supprimé.
 */
class PackageApplier
{
    /**
     * Ce que l'entreprise possède déjà (lecture seule). Filtre explicitement par company_id.
     *
     * @return array<string, mixed>
     */
    public static function existing(int $companyId): array
    {
        $setting = PayrollSetting::where('company_id', $companyId)->first();

        return [
            'item_codes' => PayItem::where('company_id', $companyId)->pluck('code')->all(),
            'profile_names' => PayProfile::where('company_id', $companyId)->pluck('name')->all(),
            'grid_names' => SalaryGrid::where('company_id', $companyId)->pluck('name')->all(),
            'template_names' => PayslipTemplate::where('company_id', $companyId)->pluck('name')->all(),
            'has_default_template' => PayslipTemplate::where('company_id', $companyId)->where('is_default', true)->exists(),
            'convention_name' => $setting ? (string) $setting->convention_name : null,
        ];
    }

    /** Aperçu : ce qui serait créé et ignoré, sans rien écrire. @return array<string, mixed> */
    public static function preview(PayrollPackage $package, int $companyId): array
    {
        return PackagePlanner::plan((array) $package->payload, self::existing($companyId));
    }

    /**
     * Applique le paquet. Renvoie le plan exécuté.
     *
     * @return array<string, mixed>
     */
    public static function apply(PayrollPackage $package, int $companyId, ?int $userId): array
    {
        return DB::transaction(function () use ($package, $companyId, $userId) {
            $plan = self::preview($package, $companyId);

            // 1. Rubriques (sans la rubrique de référence, résolue une fois toutes créées)
            foreach ($plan['items']['create'] as $row) {
                $item = new PayItem([
                    'code' => $row['code'], 'label' => $row['label'], 'type' => $row['type'], 'system_key' => null, 'is_system' => false,
                    'subject_to_social' => $row['subject_to_social'], 'taxable' => $row['taxable'], 'is_compulsory' => $row['is_compulsory'],
                    'calc_type' => $row['calc_type'], 'default_amount' => $row['default_amount'], 'default_rate' => $row['default_rate'],
                    'show_on_payslip' => true, 'sort' => $row['sort'], 'is_active' => true, 'notes' => $row['notes'],
                ]);
                $item->company_id = $companyId;
                $item->save();

                foreach ($row['scale'] as $scale) {
                    PayItemScaleRow::create(['pay_item_id' => $item->id, 'from_years' => $scale['from_years'], 'rate' => $scale['rate']]);
                }
            }

            $ids = PayItem::where('company_id', $companyId)->get()->mapWithKeys(fn (PayItem $i) => [strtoupper($i->code) => $i->id])->all();

            foreach ($plan['items']['create'] as $row) {
                if ($row['ref_code'] !== null && isset($ids[$row['ref_code']])) {
                    PayItem::where('company_id', $companyId)->where('id', $ids[$row['code']])->update(['ref_item_id' => $ids[$row['ref_code']]]);
                }
            }

            // 2. Modèles de traitement
            foreach ($plan['profiles']['create'] as $row) {
                $profile = new PayProfile(['name' => $row['name'], 'convention' => $row['convention'], 'description' => $row['description'], 'is_active' => true]);
                $profile->company_id = $companyId;
                $profile->save();

                foreach ($row['lines'] as $line) {
                    if (isset($ids[$line['code']])) {
                        PayProfileItem::create(['pay_profile_id' => $profile->id, 'pay_item_id' => $ids[$line['code']], 'amount' => $line['amount'], 'rate' => $line['rate']]);
                    }
                }
            }

            // 3. Grilles salariales
            foreach ($plan['grids']['create'] as $row) {
                $grid = new SalaryGrid(['name' => $row['name'], 'convention' => $row['convention'], 'effective_from' => null, 'is_active' => true]);
                $grid->company_id = $companyId;
                $grid->save();

                foreach ($row['grades'] as $g => $gradeRow) {
                    $grade = SalaryGrade::create(['salary_grid_id' => $grid->id, 'code' => $gradeRow['code'], 'label' => $gradeRow['label'], 'sort' => $g]);

                    foreach ($gradeRow['steps'] as $s => $stepRow) {
                        SalaryStep::create(['salary_grade_id' => $grade->id, 'label' => $stepRow['label'], 'amount' => $stepRow['amount'], 'sort' => $s]);
                    }
                }
            }

            // 4. Modèle de bulletin (par défaut seulement si l'entreprise n'en a pas)
            if ($plan['template']['action'] === 'create') {
                $t = new PayslipTemplate($plan['template']['row'] + ['is_default' => $plan['template']['make_default']]);
                $t->company_id = $companyId;
                $t->save();
            }

            // 5. Réglages : uniquement le nom de la convention, et seulement s'il est vide
            if ($plan['settings']['action'] === 'fill') {
                PayrollSetting::forCompany($companyId)->update(['convention_name' => $plan['settings']['convention_name']]);
            }

            // 6. Historique
            $application = new PayrollPackageApplication([
                'payroll_package_id' => $package->id, 'package_code' => $package->code, 'package_name' => $package->name,
                'package_version' => $package->version, 'was_validated' => $package->isValidated(),
                'applied_by' => $userId, 'report' => PackagePlanner::summary($plan),
            ]);
            $application->company_id = $companyId;
            $application->save();

            return $plan;
        });
    }
}
