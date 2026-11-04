<?php

namespace App\Livewire\Subscription;

use App\Models\Employee;
use App\Models\PlatformProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\PlanGate;
use App\Services\SubscriptionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * « Mon abonnement » : côté entreprise. Réservé au propriétaire du compte (et à qui a le droit de lire les paramètres).
 * Volontairement accessible même quand l'abonnement est expiré : c'est la page qui explique comment régulariser.
 */
#[Layout('components.layouts.app')]
#[Title('Mon abonnement')]
class MySubscription extends Component
{
    public function mount(): void
    {
        $u = auth()->user();

        abort_unless($u->company_id && ($u->is_company_owner || $u->canDo('read', 'settings')), 403);
    }

    public function render()
    {
        $cid = (int) auth()->user()->company_id;
        $sub = Subscription::with('plan.features')->where('company_id', $cid)->latest('id')->first();
        $employees = Employee::where('status', 'active')->count();   // limité à l'entreprise par le scope de société
        $max = $sub?->plan?->max_employees;

        return view('livewire.subscription.my-subscription', [
            'sub' => $sub,
            'cycle' => $sub ? app(SubscriptionService::class)->cycleInfo($sub) : null,
            'employees' => $employees,
            'max' => $max,
            'usage' => $max ? min(100, (int) round($employees / $max * 100)) : null,
            'payments' => SubscriptionPayment::where('company_id', $cid)->latest('paid_at')->latest('id')->limit(24)->get(),
            'state' => PlanGate::profile($cid)['state'],
            'contact' => PlatformProfile::current(),
            'features' => collect(config('plans.features'))->groupBy('module', true),
        ]);
    }
}
