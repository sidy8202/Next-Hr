<?php

namespace App\Livewire\Platform;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.platform')]
#[Title('Entreprises')]
class CompanyIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'statut')] public string $status = '';
    #[Url] public string $sector = '';
    #[Url(as: 'formule')] public string $plan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('companies.view'), 403);
    }

    public function updated($name): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function companies()
    {
        $latest = Subscription::selectRaw('max(id)')->groupBy('company_id');

        $page = Company::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->sector !== '', fn ($q) => $q->where('sector', $this->sector))
            ->when($this->plan !== '', fn ($q) => $q->whereIn('id', Subscription::select('company_id')->whereIn('id', $latest)->where('plan_id', $this->plan)))
            ->latest()->paginate(15);

        $ids = $page->pluck('id');
        $subs = Subscription::with('plan:id,name,color,max_employees')->whereIn('id', Subscription::selectRaw('max(id)')->whereIn('company_id', $ids)->groupBy('company_id'))->get()->keyBy('company_id');
        $emp = Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'active')->whereIn('company_id', $ids)->selectRaw('company_id, count(*) c')->groupBy('company_id')->pluck('c', 'company_id');

        $page->getCollection()->transform(function ($c) use ($subs, $emp) {
            $c->current_sub = $subs->get($c->id);
            $c->active_employees = (int) ($emp[$c->id] ?? 0);

            return $c;
        });

        return $page;
    }

    public function render()
    {
        return view('livewire.platform.company-index', ['plans' => Plan::orderBy('sort')->get(['id', 'name'])]);
    }
}
