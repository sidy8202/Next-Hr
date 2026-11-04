<?php

namespace App\Livewire\Platform;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CompanyAdminService;
use App\Services\PlanGate;
use App\Services\PlatformStats;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionMailer;
use App\Services\SubscriptionService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Layout('components.layouts.platform')]
#[Title('Entreprise')]
class CompanyShow extends Component
{
    public Company $company;

    public string $reason = '';
    public array $sub = [];
    public array $pay = [];
    public string $cancelReason = '';

    public function mount(Company $company): void
    {
        abort_unless(auth()->user()->platformCan('companies.view'), 403);
        $this->company = $company;
        $this->resetForms();
    }

    private function resetForms(): void
    {
        $this->sub = ['plan_id' => '', 'months' => 12, 'discount_percent' => config('platform.default_discounts.12', 0), 'price_override' => '', 'trial_days' => '', 'starts_at' => now()->toDateString(), 'notes' => ''];
        $this->pay = ['amount' => $this->defaultPayAmount(), 'paid_at' => now()->toDateString(), 'method' => 'mobile_money', 'reference' => '', 'note' => '', 'send_invoice' => true];
        $this->reason = '';
        $this->cancelReason = '';
    }

    /** Montant proposé pour le prochain paiement : le reste à payer (jamais le prix entier si une tranche a déjà été versée). */
    private function defaultPayAmount(): string
    {
        $sub = $this->currentSub();
        $remaining = $sub ? app(SubscriptionService::class)->cycleInfo($sub)['remaining'] : 0;

        return $remaining > 0 ? (string) $remaining : '';
    }

    private function currentSub(): ?Subscription
    {
        return Subscription::with('plan')->where('company_id', $this->company->id)->latest('id')->first();
    }

    public function updatedSubMonths($value): void
    {
        $this->sub['discount_percent'] = config('platform.default_discounts.'.(int) $value, 0);
    }

    public function suspend(): void
    {
        $this->guarded(fn () => app(CompanyAdminService::class)->suspend($this->company, $this->reason, auth()->user()), 'Entreprise suspendue.');
    }

    public function reactivate(): void
    {
        $this->guarded(fn () => app(CompanyAdminService::class)->reactivate($this->company, auth()->user()), 'Entreprise réactivée.');
    }

    public function subscribe(): void
    {
        $plan = Plan::with('prices')->find($this->sub['plan_id'] ?? null);

        if (! $plan) {
            $this->addError('sub', 'Choisissez une formule.');

            return;
        }

        $this->guarded(fn () => app(SubscriptionService::class)->subscribe($this->company, $plan, (int) $this->sub['months'], $this->sub, auth()->user()), 'Abonnement créé.');
    }

    public function recordPayment(): void
    {
        $sub = $this->currentSub();
        abort_unless($sub, 404);

        $payment = null;
        $send = (bool) ($this->pay['send_invoice'] ?? false);

        $this->guarded(function () use ($sub, &$payment) {
            $payment = app(SubscriptionService::class)->recordPayment($sub, $this->pay, auth()->user());
        }, 'Paiement enregistré.');

        if ($payment && $send) {
            $this->sendInvoiceMail($payment);
        }
    }

    public function resendInvoice(int $paymentId): void
    {
        abort_unless(auth()->user()->platformCan('payments.record'), 403);

        $payment = SubscriptionPayment::where('company_id', $this->company->id)->findOrFail($paymentId);
        $this->sendInvoiceMail($payment);
    }

    private function sendInvoiceMail(SubscriptionPayment $payment): void
    {
        $r = app(SubscriptionMailer::class)->sendInvoice($payment, auth()->user());

        $this->dispatch('notify', type: $r['ok'] ? 'success' : 'error', message: $r['ok'] ? 'Facture envoyée à '.implode(', ', $r['to']).'.' : 'Facture non envoyée : '.$r['error']);
    }

    public function cancelSubscription(): void
    {
        $sub = $this->currentSub();
        abort_unless($sub, 404);

        $this->guarded(fn () => app(SubscriptionService::class)->cancel($sub, $this->cancelReason, auth()->user()), 'Abonnement annulé.');
    }

    private function guarded(callable $action, string $success): void
    {
        try {
            $action();
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('form', config('app.debug') ? 'Action impossible : '.$e->getMessage() : 'Action impossible : rien n’a été enregistré.');

            return;
        }

        $this->company->refresh();
        PlatformStats::forget();
        $this->resetForms();
        $this->dispatch('notify', type: 'success', message: $success);
    }

    public function render()
    {
        $current = $this->currentSub();
        $employees = Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('company_id', $this->company->id)->where('status', 'active')->count();

        return view('livewire.platform.company-show', [
            'current' => $current,
            'employees' => $employees,
            'max' => $current?->plan?->max_employees,
            'plans' => Plan::with('prices')->orderBy('sort')->get(),
            'history' => Subscription::with('plan:id,name')->where('company_id', $this->company->id)->latest('id')->get(),
            'payments' => \App\Models\SubscriptionPayment::where('company_id', $this->company->id)->latest('paid_at')->latest('id')->limit(15)->get(),
            'cycle' => $current ? app(SubscriptionService::class)->cycleInfo($current) : null,
            'gate' => PlanGate::profile($this->company),
            'mailLogs' => \App\Models\PlatformMailLog::where('company_id', $this->company->id)->latest('id')->limit(10)->get(),
            'recipients' => app(SubscriptionMailer::class)->recipients($this->company),
            'can' => [
                'manage' => auth()->user()->platformCan('companies.manage'),
                'suspend' => auth()->user()->platformCan('companies.suspend'),
                'subs' => auth()->user()->platformCan('subscriptions.manage'),
                'pay' => auth()->user()->platformCan('payments.record'),
            ],
        ]);
    }
}
