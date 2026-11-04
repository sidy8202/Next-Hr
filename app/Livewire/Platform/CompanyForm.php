<?php

namespace App\Livewire\Platform;

use App\Models\Company;
use App\Services\CompanyAdminService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Layout('components.layouts.platform')]
#[Title('Entreprise')]
class CompanyForm extends Component
{
    public ?int $companyId = null;

    public string $name = '';
    public string $sector = '';
    public string $country = 'ML';
    public string $email = '';
    public string $phone = '';
    public string $timezone = 'Africa/Bamako';
    public string $locale = 'fr';
    public string $currency = 'XOF';

    public string $owner_name = '';
    public string $owner_email = '';

    public function mount(?int $company = null): void
    {
        abort_unless(auth()->user()->platformCan('companies.manage'), 403);

        if ($company) {
            $c = Company::findOrFail($company);
            $this->companyId = $c->id;

            foreach (['name', 'sector', 'country', 'email', 'phone', 'timezone', 'locale', 'currency'] as $f) {
                $this->{$f} = (string) $c->{$f};
            }
        }
    }

    public function save()
    {
        abort_unless(auth()->user()->platformCan('companies.manage'), 403);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'sector' => ['required', Rule::in(array_keys(config('platform.sectors')))],
            'country' => ['required', Rule::in(array_keys(config('platform.countries')))],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:5'],
            'currency' => ['required', 'string', 'size:3'],
            'owner_name' => [$this->companyId ? 'nullable' : 'required', 'string', 'max:150'],
            'owner_email' => [$this->companyId ? 'nullable' : 'required', 'email', 'max:150'],
        ], [], ['name' => 'nom', 'sector' => 'secteur d’activité', 'country' => 'pays', 'owner_name' => 'nom du propriétaire', 'owner_email' => 'e-mail du propriétaire']);

        $data = $this->only(['name', 'sector', 'country', 'email', 'phone', 'timezone', 'locale', 'currency']);
        $data['currency'] = strtoupper($data['currency']);
        $service = app(CompanyAdminService::class);

        try {
            if ($this->companyId) {
                $company = $service->update(Company::findOrFail($this->companyId), $data, auth()->user());
                session()->flash('status', 'Entreprise mise à jour.');
            } else {
                [$company, $temp] = $service->create($data, ['name' => $this->owner_name, 'email' => $this->owner_email], auth()->user());
                session()->flash('status', 'Entreprise créée.');
                session()->flash('temp_password', ['email' => $this->owner_email, 'password' => $temp]);
            }
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('form', config('app.debug') ? 'Enregistrement impossible : '.$e->getMessage() : 'Enregistrement impossible : rien n’a été enregistré.');

            return;
        }

        return $this->redirectRoute('platform.companies.show', ['company' => $company->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.platform.company-form');
    }
}
