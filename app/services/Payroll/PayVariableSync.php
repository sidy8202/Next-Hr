<?php

namespace App\Services\Payroll;

use App\Models\Absence;
use App\Models\ExplanationCase;
use App\Models\LeaveType;
use App\Models\PayItem;
use App\Models\PayrollVariable;
use App\Models\PayrollVariableSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Génère les variables PROPOSÉES d'un mois à partir des sources existantes de l'application :
 * absences non justifiées, congés sans solde approuvés, mises à pied décidées, jours travaillés à payer.
 * Une variable proposée n'est jamais utilisée par le calcul tant que le RH ne l'a pas confirmée.
 */
class PayVariableSync
{
    /** Rubriques système dont le lot 13c a besoin : créées si le catalogue de départ n'a pas été chargé. */
    public const SYSTEM_ITEMS = [
        'overtime' => ['100010', 'Heures supplémentaires', 'gain', true, true, 30],
        'worked_days' => ['100020', 'Jours travaillés payés', 'gain', true, true, 35],
        'absence_deduction' => ['300001', 'Absence non rémunérée', 'gross_deduction', true, true, 300],
        'suspension' => ['300002', 'Mise à pied', 'gross_deduction', true, true, 310],
        'loan' => ['390001', 'Remboursement de prêt', 'deduction_individual', false, false, 900],
        'advance' => ['390002', "Retenue d'avance sur salaire", 'deduction_individual', false, false, 910],
    ];

    public static function ensureSystemItems(int $companyId): void
    {
        foreach (self::SYSTEM_ITEMS as $key => [$code, $label, $type, $social, $taxable, $sort]) {
            if (PayItem::where('system_key', $key)->exists()) {
                continue;
            }

            for ($i = 0; $i < 30 && PayItem::where('code', $code)->exists(); $i++) {
                $code = (string) ((int) $code + 1);
            }

            $item = new PayItem([
                'code' => $code, 'label' => $label, 'type' => $type, 'system_key' => $key, 'is_system' => true,
                'subject_to_social' => $social, 'taxable' => $taxable, 'is_compulsory' => true, 'calc_type' => 'system',
                'show_on_payslip' => true, 'sort' => $sort, 'is_active' => true, 'notes' => 'Rubrique fournie par le moteur de paie.',
            ]);
            $item->company_id = $companyId;
            $item->save();
        }
    }

    /** @return array{created: int, updated: int, removed: int, notes: array<int, string>} */
    public static function run(string $period, int $companyId): array
    {
        self::ensureSystemItems($companyId);

        [$start, $end] = PayrollDays::monthBounds($period);
        $setting = PayrollVariableSetting::forCompany($companyId);
        $notes = [];
        $proposals = [];

        // 1. Absences non justifiées : on ne retient que les jours qui n'ont pas déjà été pris sur le solde de congés.
        if ($setting->unjustified_absence_unpaid) {
            foreach (Absence::where('justified', false)->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->get() as $a) {
                $from = Carbon::parse($a->start_date)->toDateString();
                $to = Carbon::parse($a->end_date)->toDateString();
                $days = (float) $a->days;
                $share = PayrollDays::proportionalDays($days, $from, $to, $start, $end);
                $deducted = $days > 0 ? (float) ($a->deducted_days ?? 0) * $share / $days : 0.0;
                $qty = round(max(0.0, $share - $deducted), 2);

                if ($qty > 0) {
                    $proposals[] = [
                        'employee_id' => (int) $a->employee_id, 'kind' => 'unpaid_absence', 'quantity' => $qty, 'unit' => 'days', 'days_mode' => null,
                        'source_type' => 'absence', 'source_id' => (int) $a->id,
                        'source_ref' => 'Absence non justifiée du ' . Carbon::parse($from)->format('d/m/Y') . ' au ' . Carbon::parse($to)->format('d/m/Y'),
                    ];
                }
            }
        }

        // 2. Congés sans solde approuvés (type de congé non payé).
        try {
            $leaveClass = 'App\\Models\\LeaveRequest';
            $unpaidTypes = LeaveType::where('paid', false)->pluck('id');

            if (class_exists($leaveClass) && $unpaidTypes->isNotEmpty()) {
                foreach ($leaveClass::whereIn('leave_type_id', $unpaidTypes)->where('status', 'approved')->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->get() as $r) {
                    $from = Carbon::parse($r->start_date)->toDateString();
                    $to = Carbon::parse($r->end_date)->toDateString();
                    $qty = PayrollDays::proportionalDays((float) $r->days, $from, $to, $start, $end);

                    if ($qty > 0) {
                        $proposals[] = [
                            'employee_id' => (int) $r->employee_id, 'kind' => 'unpaid_absence', 'quantity' => $qty, 'unit' => 'days', 'days_mode' => null,
                            'source_type' => 'leave_request', 'source_id' => (int) $r->id,
                            'source_ref' => 'Congé sans solde du ' . Carbon::parse($from)->format('d/m/Y') . ' au ' . Carbon::parse($to)->format('d/m/Y'),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $notes[] = 'Les congés sans solde n\'ont pas pu être lus : ' . $e->getMessage();
        }

        // 3. Mises à pied décidées et non rémunérées (dossiers de demande d'explication).
        $holidays = self::holidays($start, $end, $companyId);

        foreach (ExplanationCase::where('status', 'decided')->whereNotNull('suspension_start')->whereNotNull('suspension_end')
            ->where(fn ($q) => $q->where('suspension_paid', false)->orWhereNull('suspension_paid'))
            ->whereDate('suspension_start', '<=', $end)->whereDate('suspension_end', '>=', $start)->get() as $c) {
            $from = Carbon::parse($c->suspension_start)->toDateString();
            $to = Carbon::parse($c->suspension_end)->toDateString();
            $qty = PayrollDays::countDays($from, $to, $setting->suspension_count_mode, $setting->restDays(), $holidays, $start, $end);

            if ($qty > 0) {
                $proposals[] = [
                    'employee_id' => (int) $c->employee_id, 'kind' => 'suspension', 'quantity' => (float) $qty, 'unit' => 'days', 'days_mode' => $setting->suspension_count_mode,
                    'source_type' => 'explanation_case', 'source_id' => (int) $c->id,
                    'source_ref' => 'Mise à pied du ' . Carbon::parse($from)->format('d/m/Y') . ' au ' . Carbon::parse($to)->format('d/m/Y'),
                ];
            }
        }

        // 4. Jours travaillés dont la compensation choisie est le paiement.
        foreach (self::overtimeProposals($start, $end, $companyId, $notes) as $p) {
            $proposals[] = $p;
        }

        return self::persist($period, $companyId, $proposals, $notes);
    }

    /**
     * Source des jours travaillés (OvertimeRecord) : lue de façon défensive, car sa structure n'était pas connue au moment de l'écriture.
     * Si les colonnes attendues ne sont pas reconnues, AUCUNE proposition n'est faite (jamais de proposition au hasard).
     * C'est le seul endroit à ajuster pour brancher cette source.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function overtimeProposals(string $start, string $end, int $companyId, array &$notes): array
    {
        $class = 'App\\Models\\OvertimeRecord';

        if (! class_exists($class)) {
            return [];
        }

        try {
            $table = (new $class())->getTable();

            if (! Schema::hasTable($table)) {
                return [];
            }

            $cols = Schema::getColumnListing($table);

            foreach (['employee_id', 'worked_on', 'days'] as $required) {
                if (! in_array($required, $cols, true)) {
                    $notes[] = "Jours travaillés : la colonne « $required » est introuvable, aucune proposition automatique.";

                    return [];
                }
            }

            $compCol = null;

            foreach (['compensation', 'compensation_type', 'compensation_mode', 'compensation_choice', 'paid_as'] as $candidate) {
                if (in_array($candidate, $cols, true)) {
                    $compCol = $candidate;
                    break;
                }
            }

            if (! $compCol) {
                $notes[] = 'Jours travaillés : le mode de compensation (paiement ou récupération) n\'est pas reconnu, aucune proposition automatique. Saisissez-les à la main.';

                return [];
            }

            $query = $class::query()->whereDate('worked_on', '>=', $start)->whereDate('worked_on', '<=', $end);

            if (in_array('company_id', $cols, true)) {
                $query->where('company_id', $companyId);
            }

            if (in_array('status', $cols, true)) {
                $query->where('status', 'approved');
            }

            $paidValues = ['pay', 'paid', 'payment', 'money', 'cash', 'paye', 'payé', 'paiement', 'salary', 'salaire'];
            $out = [];

            foreach ($query->get() as $r) {
                $mode = mb_strtolower((string) $r->getAttribute($compCol));
                $qty = (float) $r->getAttribute('days');

                if (! in_array($mode, $paidValues, true) || $qty <= 0) {
                    continue;
                }

                $out[] = [
                    'employee_id' => (int) $r->getAttribute('employee_id'), 'kind' => 'worked_days', 'quantity' => $qty, 'unit' => 'days', 'days_mode' => null,
                    'source_type' => 'overtime_record', 'source_id' => (int) $r->getKey(),
                    'source_ref' => 'Jour(s) travaillé(s) le ' . Carbon::parse($r->getAttribute('worked_on'))->format('d/m/Y'),
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            $notes[] = 'Les jours travaillés n\'ont pas pu être lus : ' . $e->getMessage();

            return [];
        }
    }

    /** @return array<int, string> jours fériés du mois (table « holidays » si elle existe avec une colonne « date ») */
    protected static function holidays(string $start, string $end, int $companyId): array
    {
        try {
            if (! Schema::hasTable('holidays') || ! Schema::hasColumn('holidays', 'date')) {
                return [];
            }

            $query = DB::table('holidays')->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);

            if (Schema::hasColumn('holidays', 'company_id')) {
                $query->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'));
            }

            return $query->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Enregistre les propositions : crée les nouvelles, rafraîchit celles qui n'ont pas encore été traitées,
     * retire celles dont la source a disparu. Une ligne confirmée ou ignorée par le RH n'est jamais modifiée.
     *
     * @return array{created: int, updated: int, removed: int, notes: array<int, string>}
     */
    protected static function persist(string $period, int $companyId, array $proposals, array $notes): array
    {
        $created = 0;
        $updated = 0;
        $keys = [];

        foreach ($proposals as $p) {
            $keys[$p['employee_id'] . '|' . $p['source_type'] . '|' . $p['source_id']] = true;

            $existing = PayrollVariable::where('period', $period)->where('employee_id', $p['employee_id'])
                ->where('source_type', $p['source_type'])->where('source_id', $p['source_id'])->first();

            if (! $existing) {
                $row = new PayrollVariable($p + ['period' => $period, 'status' => 'proposed']);
                $row->company_id = $companyId;
                $row->save();
                $created++;
            } elseif ($existing->status === 'proposed' && ((float) $existing->quantity !== (float) $p['quantity'] || $existing->source_ref !== $p['source_ref'])) {
                $existing->update(['quantity' => $p['quantity'], 'source_ref' => $p['source_ref'], 'days_mode' => $p['days_mode']]);
                $updated++;
            } elseif ($existing->status === 'confirmed' && (float) $existing->quantity !== (float) $p['quantity']) {
                $notes[] = 'La source de « ' . $existing->source_ref . ' » a changé depuis la confirmation : vérifiez la quantité confirmée.';
            }
        }

        $removed = 0;

        foreach (PayrollVariable::where('period', $period)->where('status', 'proposed')->whereNotNull('source_type')->get() as $stale) {
            if (! isset($keys[$stale->employee_id . '|' . $stale->source_type . '|' . $stale->source_id])) {
                $stale->delete();
                $removed++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'removed' => $removed, 'notes' => array_values(array_unique($notes))];
    }
}
