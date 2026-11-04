<?php

namespace App\Livewire\Platform;

use App\Models\PlatformMailLog;
use App\Models\Company;
use App\Models\PlatformProfile;
use App\Models\SubscriptionPayment;
use App\Services\PlatformAudit;
use App\Services\SubscriptionMailer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.platform')]
#[Title('Facturation')]
class BillingSettings extends Component
{
    use WithFileUploads;

    public string $legal_name = '';
    public string $address = '';
    public string $city = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $rccm = '';
    public string $nif = '';
    public string $bank_details = '';
    public string $tax_note = '';
    public string $footer_text = '';
    public $logo = null;
    public bool $remove_logo = false;
    public string $test_to = '';

    // Détails : e-mail ou facture ouverts dans le panneau
    public ?int $logId = null;
    public ?int $invoiceId = null;
    public string $invoiceSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('plans.manage'), 403);

        $p = PlatformProfile::current();

        foreach (['legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'bank_details', 'tax_note', 'footer_text'] as $f) {
            $this->{$f} = (string) $p->{$f};
        }

        $this->test_to = (string) auth()->user()->email;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->platformCan('plans.manage'), 403);

        $this->validate([
            'legal_name' => ['required', 'string', 'max:150'], 'address' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:60'], 'email' => ['nullable', 'email', 'max:150'], 'website' => ['nullable', 'string', 'max:150'],
            'rccm' => ['nullable', 'string', 'max:80'], 'nif' => ['nullable', 'string', 'max:80'], 'bank_details' => ['nullable', 'string', 'max:1000'],
            'tax_note' => ['nullable', 'string', 'max:500'], 'footer_text' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ], [], ['legal_name' => 'nom officiel', 'bank_details' => 'coordonnées de règlement', 'tax_note' => 'mention fiscale']);

        $p = PlatformProfile::current();
        $data = $this->only(['legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'bank_details', 'tax_note', 'footer_text']);
        $old = $p->logo_path;

        if ($this->logo) {
            $data['logo_path'] = $this->logo->storeAs('platform/branding', Str::uuid().'.'.$this->logo->extension(), 'local');
        } elseif ($this->remove_logo) {
            $data['logo_path'] = null;
        }

        $p->update($data);

        if ($old && array_key_exists('logo_path', $data) && $data['logo_path'] !== $old) {
            Storage::disk('local')->delete($old);
        }

        PlatformAudit::log('billing_profile_updated', 'Paramètres de facturation modifiés');
        $this->reset(['logo', 'remove_logo']);
        $this->dispatch('notify', type: 'success', message: 'Paramètres de facturation enregistrés.');
    }

    public function sendTest(): void
    {
        abort_unless(auth()->user()->platformCan('plans.manage'), 403);

        $this->validate(['test_to' => ['required', 'email']], [], ['test_to' => 'adresse de test']);

        $r = app(SubscriptionMailer::class)->sendTest($this->test_to, auth()->user());
        $this->dispatch('notify', type: $r['ok'] ? 'success' : 'error', message: $r['ok'] ? 'E-mail de test envoyé à '.$this->test_to.'.' : 'Échec de l’envoi : '.$r['error']);
    }

    public function openLog(int $id): void
    {
        $this->invoiceId = null;
        $this->logId = $this->logId === $id ? null : $id;
    }

    public function openInvoice(int $id): void
    {
        $this->logId = null;
        $this->invoiceId = $this->invoiceId === $id ? null : $id;
    }

    public function closeDetail(): void
    {
        $this->logId = $this->invoiceId = null;
    }

    public function resendInvoice(int $paymentId): void
    {
        abort_unless(auth()->user()->platformCan('payments.record'), 403);

        $payment = SubscriptionPayment::findOrFail($paymentId);
        $r = app(SubscriptionMailer::class)->sendInvoice($payment, auth()->user());

        $this->dispatch('notify', type: $r['ok'] ? 'success' : 'error', message: $r['ok'] ? 'Facture envoyée à '.implode(', ', $r['to']).'.' : 'Facture non envoyée : '.$r['error']);
    }

    public function render()
    {
        $log = $this->logId ? PlatformMailLog::with('triggeredBy:id,name')->find($this->logId) : null;
        $search = trim($this->invoiceSearch);

        return view('livewire.platform.billing-settings', [
            'profile' => PlatformProfile::current(),
            'logs' => PlatformMailLog::with('triggeredBy:id,name')->latest('id')->limit(25)->get(),
            'mailer' => config('mail.default'),
            'log' => $log,
            'logCompany' => $log?->company_id ? Company::find($log->company_id) : null,
            'logPayment' => $log?->payment_id ? SubscriptionPayment::find($log->payment_id) : null,
            'invoice' => $this->invoiceId ? SubscriptionPayment::with(['company:id,name,email', 'subscription.plan:id,name', 'recorder:id,name'])->find($this->invoiceId) : null,
            'invoices' => SubscriptionPayment::with('company:id,name')
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('invoice_number', 'like', "%{$search}%")->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"))))
                ->latest('paid_at')->latest('id')->limit(30)->get(),
            'canResend' => auth()->user()->platformCan('payments.record'),
        ]);
    }
}
