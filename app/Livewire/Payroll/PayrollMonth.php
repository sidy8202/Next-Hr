<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LoanInstallment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRule;
use App\Models\PayrollVariable;
use App\Models\PayrollVariableSetting;
use App\Models\Payslip;
use App\Models\PayslipDelivery;
use App\Services\Payroll\DeliveryState;
use App\Services\PayslipMailer;
use App\Models\User;
use App\Services\Payroll\LoanSchedule;
use App\Services\Payroll\PayrollDays;
use App\Services\Payroll\PayrollRunner;
use App\Services\Payroll\Proration;
use App\Services\Payroll\RunSummary;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * La paie du mois en une seule page, en quatre étapes : Préparer, Calculer, Contrôler, Clôturer.
 * Le traitement mensuel ordinaire ne demande pas de quitter cette page (hormis la confirmation des variables).
 */
class PayrollMonth extends Component
{
    use PayrollGuards;

    public string $period = '';
    public string $statusFilter = '';
    public bool $confirmClose = false;
    public string $toleranceInput = '';

    /** @var array<int, string> notes d'acceptation des écarts, par bulletin */
    public array $ackNotes = [];

    public ?int $regularizeId = null;
    public string $regDirection = 'add';
    public string $regAmount = '';
    public string $regReason = '';

    public string $flash = '';
    public string $problem = '';

    public function mount(): void
    {
        $this->allow('read', 'payroll_periods');

        // On ouvre sur la plus ancienne paie encore ouverte, sinon sur le mois courant.
        $open = PayrollPeriod::where('status', '!=', 'closed')->orderBy('period')->value('period');
        $this->period = $open ?: now()->format('Y-m');
        $this->toleranceInput = (string) PayrollVariableSetting::forCompany($this->companyId())->net_tolerance;
    }

    public function updatedPeriod(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->period)) {
            $this->period = now()->format('Y-m');
        }

        $this->resetMessages();
    }

    public function previousMonth(): void
    {
        $this->period = LoanSchedule::addMonths($this->period, -1);
        $this->resetMessages();
    }

    public function nextMonth(): void
    {
        $this->period = LoanSchedule::addMonths($this->period, 1);
        $this->resetMessages();
    }

    public function selectPeriod(string $period): void
    {
        $this->period = $period;
        $this->updatedPeriod();
    }

    protected function resetMessages(): void
    {
        $this->flash = '';
        $this->problem = '';
        $this->confirmClose = false;
        $this->regularizeId = null;
    }

    // ───── Étape 2 : calculer ─────

    public function calculate(): void
    {
        $this->allow('update', 'payroll_periods');
        $this->resetMessages();

        try {
            $period = PayrollRunner::period($this->period, $this->companyId());
            $result = PayrollRunner::calculate($period, auth()->id());
        } catch (\RuntimeException $e) {
            $this->problem = $e->getMessage();

            return;
        }

        $this->audit('update', 'payroll_periods', $period->id, null, ['calculated' => $result['processed'], 'skipped' => count($result['skipped']), 'period' => $this->period]);
        $this->flash = $result['processed'] . ' bulletin(s) calculé(s)' . ($result['skipped'] ? ', ' . count($result['skipped']) . ' employé(s) non traité(s) (voir ci-dessous)' : '') . '.';
    }

    // ───── Étape 3 : contrôler ─────

    public function acknowledge(int $payslipId): void
    {
        $this->allow('update', 'payroll_periods');
        $payslip = Payslip::findOrFail($payslipId);

        if ($payslip->status === 'final') {
            return;
        }

        $note = trim((string) ($this->ackNotes[$payslipId] ?? ''));
        $payslip->update(['acknowledged_by' => auth()->id(), 'acknowledged_at' => now(), 'acknowledged_note' => $note !== '' ? mb_substr($note, 0, 500) : null]);
        $this->audit('update', 'payroll_periods', $payslip->payroll_period_id, null, ['acknowledged_gap' => $payslip->structure_gap, 'employee_id' => $payslip->employee_id, 'note' => $note]);
        unset($this->ackNotes[$payslipId]);
    }

    public function unacknowledge(int $payslipId): void
    {
        $this->allow('update', 'payroll_periods');
        $payslip = Payslip::findOrFail($payslipId);

        if ($payslip->status === 'final') {
            return;
        }

        $payslip->update(['acknowledged_by' => null, 'acknowledged_at' => null, 'acknowledged_note' => null]);
    }

    public function saveTolerance(): void
    {
        $this->allow('update', 'payroll_settings');

        $value = $this->digits($this->toleranceInput);
        $setting = PayrollVariableSetting::forCompany($this->companyId());
        $old = (int) $setting->net_tolerance;
        $setting->net_tolerance = $value;
        $setting->save();

        $this->audit('update', 'payroll_settings', $setting->id, ['net_tolerance' => $old], ['net_tolerance' => $value]);
        $this->flash = 'Tolérance enregistrée : ' . number_format($value, 0, ',', ' ') . ' FCFA. Recalculez la paie pour l\'appliquer.';
    }

    // ───── Étape 4 : clôturer ─────

    public function closePeriod(): void
    {
        $this->allow('update', 'payroll_periods');
        $this->problem = '';
        $this->flash = '';

        if (! $this->confirmClose) {
            $this->problem = 'Cochez la case pour confirmer que vous avez vérifié les écarts.';

            return;
        }

        $period = PayrollPeriod::where('period', $this->period)->first();

        if (! $period) {
            $this->problem = 'Calculez d\'abord la paie du mois.';

            return;
        }

        try {
            PayrollRunner::close($period, auth()->id());
        } catch (\RuntimeException $e) {
            $this->problem = $e->getMessage();

            return;
        }

        $this->audit('update', 'payroll_periods', $period->id, ['status' => 'calculated'], ['status' => 'closed', 'period' => $this->period]);
        $this->confirmClose = false;
        $this->flash = 'Paie clôturée. Les bulletins sont définitifs : une erreur se corrige par une régularisation.';
    }

    // ───── Régularisation ─────

    public function startRegularize(int $payslipId): void
    {
        $this->allow('create', 'payslips');
        $this->regularizeId = Payslip::findOrFail($payslipId)->id;
        $this->regDirection = 'add';
        $this->regAmount = '';
        $this->regReason = '';
        $this->problem = '';
    }

    public function cancelRegularize(): void
    {
        $this->regularizeId = null;
    }

    public function saveRegularize(): void
    {
        $this->allow('create', 'payslips');
        $payslip = Payslip::findOrFail((int) $this->regularizeId);

        try {
            $variable = PayrollRunner::regularize($payslip, $this->regDirection === 'deduct' ? 'deduct' : 'add', $this->digits($this->regAmount), $this->regReason, auth()->id());
        } catch (\RuntimeException $e) {
            $this->problem = $e->getMessage();

            return;
        }

        $this->audit('create', 'payslips', $payslip->id, null, ['regularisation' => $variable->amount_override, 'direction' => $this->regDirection, 'period' => $variable->period, 'reason' => $this->regReason]);
        $this->regularizeId = null;
        $this->flash = 'Régularisation enregistrée : elle apparaît dans les variables de ' . $variable->period . ', avec son motif.';
    }

    // ───── Envoi des bulletins ─────

    public function sendOne(int $payslipId): void
    {
        $this->allow('create', 'payslips');
        $this->resetMessages();
        $payslip = Payslip::findOrFail($payslipId);

        if ($payslip->status !== 'final') {
            $this->problem = 'Seuls les bulletins d\'une paie clôturée s\'envoient.';

            return;
        }

        $r = app(PayslipMailer::class)->send($payslip, auth()->user());

        $this->audit('create', 'payslips', $payslip->id, null, ['sent' => $r['sent'], 'failed' => $r['failed'], 'employee_id' => $payslip->employee_id]);
        $this->flash = $payslip->employee_name . ' : ' . $r['sent'] . ' e-mail(s) envoyé(s)' . ($r['failed'] > 0 ? ', ' . $r['failed'] . ' en échec' : '') . ', notification envoyée dans l\'application.';

        if ($r['errors']) {
            $this->problem = implode(' ', array_slice($r['errors'], 0, 2));
        }
    }

    public function sendAll(): void
    {
        $this->sendBatch(false);
    }

    public function retryFailed(): void
    {
        $this->sendBatch(true);
    }

    /** Envoie à tous les employés ce qui n'est pas déjà parti ; $onlyFailed : uniquement les bulletins qui ont un échec. */
    protected function sendBatch(bool $onlyFailed): void
    {
        $this->allow('create', 'payslips');
        $this->resetMessages();

        $period = PayrollPeriod::where('period', $this->period)->first();

        if (! $period || ! $period->isClosed()) {
            $this->problem = 'Les bulletins s\'envoient une fois la paie clôturée.';

            return;
        }

        @set_time_limit(300);

        $payslips = Payslip::where('payroll_period_id', $period->id)->where('status', 'final')->orderBy('employee_name')->get();
        $deliveries = PayslipDelivery::whereIn('payslip_id', $payslips->pluck('id'))->get()->groupBy('payslip_id');
        $mailer = app(PayslipMailer::class);
        $sent = 0;
        $failed = 0;
        $payslipCount = 0;
        $errors = [];

        foreach ($payslips as $p) {
            if ($onlyFailed && ! $deliveries->get($p->id, collect())->contains('status', 'failed')) {
                continue;
            }

            $r = $mailer->send($p, auth()->user(), true);
            $sent += $r['sent'];
            $failed += $r['failed'];
            $payslipCount++;

            foreach ($r['errors'] as $e) {
                $errors[] = $p->employee_name . ' : ' . $e;
            }
        }

        $this->audit('create', 'payslips', null, null, ['period' => $this->period, 'bulk_sent' => $sent, 'bulk_failed' => $failed, 'payslips' => $payslipCount]);
        $this->flash = $sent . ' e-mail(s) envoyé(s) pour ' . $payslipCount . ' bulletin(s)' . ($failed > 0 ? ', ' . $failed . ' en échec' : '') . '.';

        if ($errors) {
            $this->problem = implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' …' : '');
        }
    }

    /** @return array{status: string, sent_at: ?string, error: ?string, opened_at: ?string} */
    protected function deliveryRow(PayslipDelivery $r): array
    {
        return ['status' => $r->status, 'sent_at' => $r->sent_at?->format('d/m/Y H:i'), 'error' => $r->error, 'opened_at' => $r->opened_at?->format('d/m/Y H:i')];
    }

    // ───── Affichage ─────

    public function render()
    {
        [$start, $end] = PayrollDays::monthBounds($this->period);
        $period = PayrollPeriod::where('period', $this->period)->first();
        $status = $period?->status ?? 'draft';

        $payslips = $period
            ? Payslip::where('payroll_period_id', $period->id)->orderBy('department_name')->orderBy('employee_name')->get()
            : collect();

        $filtered = $payslips->when($this->statusFilter !== '', fn ($c) => $c->where('check_status', $this->statusFilter));

        $summary = RunSummary::summarize($payslips->map(fn ($p) => [
            'department' => $p->department_name, 'net_known' => $p->net_known !== null ? (int) $p->net_known : null, 'net_to_pay' => (int) $p->net_to_pay, 'status' => $p->check_status,
        ])->all());

        $byDepartment = $filtered->groupBy(fn ($p) => $p->department_name ?: RunSummary::NO_DEPARTMENT);
        $departments = collect($summary['departments'])->map(fn ($stats, $name) => ['name' => $name, 'stats' => $stats, 'payslips' => $byDepartment->get($name, collect())])->values();

        $pending = $payslips->filter(fn ($p) => $p->check_status === 'to_check' && $p->acknowledged_at === null)->count();

        $users = User::whereIn('id', $payslips->pluck('acknowledged_by')->filter()->unique()->all())->pluck('name', 'id');

        // Suivi d'envoi devant chaque employé : e-mail professionnel, e-mail personnel, consultation dans l'application.
        $delivery = [];
        $deliverySummary = null;

        if ($status === 'closed') {
            $contacts = Employee::whereIn('id', $payslips->pluck('employee_id'))->get()->keyBy('id');
            $rows = PayslipDelivery::whereIn('payslip_id', $payslips->pluck('id'))->get()->groupBy('payslip_id');

            foreach ($payslips as $p) {
                $contact = $contacts->get($p->employee_id);
                $byChannel = $rows->get($p->id, collect())->keyBy('channel');
                $work = DeliveryState::channel($byChannel->has('work_email') ? $this->deliveryRow($byChannel['work_email']) : null, $contact?->work_email);
                $personal = DeliveryState::channel($byChannel->has('personal_email') ? $this->deliveryRow($byChannel['personal_email']) : null, $contact?->personal_email);
                $app = $byChannel->get('app');

                $delivery[$p->id] = [
                    'work' => $work,
                    'personal' => $personal,
                    'overall' => DeliveryState::overall([$work, $personal]),
                    'viewed_at' => $app?->viewed_at,
                    'opened_at' => $work['opened_at'] ?? $personal['opened_at'] ?? null,
                ];
            }

            $deliverySummary = DeliveryState::summarize(array_map(fn ($d) => ['overall' => $d['overall'], 'seen_app' => $d['viewed_at'] !== null, 'opened_email' => $d['opened_at'] !== null], array_values($delivery)));
        }

        return view('livewire.payroll.payroll-month', [
            'monthLabel' => $this->monthLabel($start),
            'periodModel' => $period,
            'status' => $status,
            'payslips' => $payslips,
            'departments' => $departments,
            'summary' => $summary,
            'stored' => $period?->summary ?? [],
            'pending' => $pending,
            'users' => $users,
            'checklist' => $status === 'closed' ? [] : $this->checklist($this->period, $start, $end),
            'recent' => PayrollPeriod::orderByDesc('period')->limit(12)->get(),
            'closer' => $period?->closer?->name,
            'canCalculate' => $this->allowed('update', 'payroll_periods') && $status !== 'closed',
            'delivery' => $delivery,
            'deliverySummary' => $deliverySummary,
            'canRegularize' => $this->allowed('create', 'payslips'),
            'canTolerance' => $this->allowed('update', 'payroll_settings'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Paie du mois']);
    }

    /**
     * Étape 1 : les points à régler avant de calculer, chacun avec son lien. Requêtes groupées, sans boucle sur la base.
     *
     * @return array<int, array{level: string, title: string, detail: string, href: ?string}>
     */
    protected function checklist(string $period, string $start, string $end): array
    {
        $items = [];

        $proposed = PayrollVariable::where('period', $period)->where('status', 'proposed')->count();
        $confirmed = PayrollVariable::where('period', $period)->where('status', 'confirmed')->count();
        $items[] = $proposed > 0
            ? ['level' => 'warn', 'title' => $proposed . ' variable(s) du mois à confirmer', 'detail' => 'Absences, mises à pied, affectations… proposées automatiquement : tant qu\'elles ne sont pas confirmées, elles ne comptent pas dans la paie.', 'href' => route('payroll.variables')]
            : ['level' => 'ok', 'title' => 'Variables du mois', 'detail' => $confirmed . ' variable(s) confirmée(s), aucune en attente.', 'href' => route('payroll.variables')];

        $due = LoanInstallment::where('due_month', $period)->where('status', 'scheduled')->whereHas('loan', fn ($q) => $q->where('status', 'active'))->get();
        $items[] = ['level' => 'ok', 'title' => 'Prêts et avances', 'detail' => $due->isEmpty() ? 'Aucune échéance ce mois-ci.' : $due->count() . ' échéance(s) à prélever, pour ' . number_format((int) $due->sum('amount'), 0, ',', ' ') . ' au total.', 'href' => route('payroll.loans')];

        $rules = PayrollRule::activeOn($end);
        $unvalidated = count(array_filter($rules, fn ($r) => ($r['status'] ?? '') !== 'validated'));
        $items[] = $rules === []
            ? ['level' => 'warn', 'title' => 'Aucune règle légale en vigueur', 'detail' => 'Les règles de paie (taux, barème de l\'ITS) doivent être chargées par la plateforme.', 'href' => null]
            : ($unvalidated > 0
                ? ['level' => 'warn', 'title' => $unvalidated . ' règle(s) légale(s) pas encore validée(s)', 'detail' => 'Faites-les valider par votre comptable ; la paie reste calculable en attendant.', 'href' => null]
                : ['level' => 'ok', 'title' => 'Règles légales', 'detail' => 'Toutes les règles en vigueur sont validées.', 'href' => null]);

        // Employés présents ce mois, sans salaire, ou sans net connu.
        $terminations = $this->terminations();
        $withSalary = EmployeeSalary::whereDate('effective_from', '<=', $end)->pluck('employee_id')->unique()->flip();
        $days = (int) \App\Models\PayrollSetting::forCompany($this->companyId())->days_basis;
        $policy = \App\Services\Payroll\PayrollInputBuilder::policy($this->companyId());
        $noSalary = [];
        $presentIds = [];

        foreach (Employee::orderBy('matricule')->get() as $e) {
            $a = $e->getAttributes();
            $hire = ! empty($a['hire_date']) ? substr((string) $a['hire_date'], 0, 10) : null;

            if (Proration::presenceFactor($hire, $terminations[$e->id] ?? null, $start, $end, $days, $policy) <= 0.0) {
                continue;
            }

            $presentIds[] = $e->id;

            if ($withSalary->has($e->id)) {
                continue;
            }

            $noSalary[] = trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?: ('#' . $e->id);
        }

        $items[] = $noSalary
            ? ['level' => 'warn', 'title' => count($noSalary) . ' employé(s) sans salaire enregistré', 'detail' => implode(', ', array_slice($noSalary, 0, 5)) . (count($noSalary) > 5 ? '…' : '') . ' : ils ne seront pas payés tant que leur salaire n\'est pas saisi.', 'href' => route('payroll.salaries')]
            : ['level' => 'ok', 'title' => 'Salaires', 'detail' => 'Tous les employés présents ont un salaire enregistré.', 'href' => route('payroll.salaries')];

        $withoutNet = EmployeeSalary::inForceOn($end)->whereNull('target_net')->whereIn('employee_id', $presentIds)->count();
        $items[] = $withoutNet > 0
            ? ['level' => 'warn', 'title' => $withoutNet . ' employé(s) sans net connu', 'detail' => 'Sans net connu, leur paie ne peut pas être vérifiée. Vous pouvez les saisir, les importer ou les compléter d\'après le calcul.', 'href' => route('payroll.salaries')]
            : ['level' => 'ok', 'title' => 'Nets connus', 'detail' => 'Tous les salaires ont un net connu.', 'href' => route('payroll.salaries')];

        return $items;
    }

    /** @return array<int, string> dernier jour de présence par employé (table terminations, si elle existe) */
    protected function terminations(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('terminations')) {
                return [];
            }

            return DB::table('terminations')->pluck('last_day', 'employee_id')->map(fn ($d) => substr((string) $d, 0, 10))->all();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function monthLabel(string $start): string
    {
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return $months[(int) substr($start, 5, 2) - 1] . ' ' . substr($start, 0, 4);
    }
}
