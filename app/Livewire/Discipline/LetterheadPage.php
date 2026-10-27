<?php

namespace App\Livewire\Discipline;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Letterhead;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Papier à en-tête des lettres officielles (informations de l'entreprise, logo, délai de réponse par défaut). */
#[Layout('components.layouts.app')]
#[Title('En-tête des lettres')]
class LetterheadPage extends Component
{
    use AuthorizesResource, WithFileUploads;

    public string $legal_name = '';
    public string $address = '';
    public string $city = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $rccm = '';
    public string $nif = '';
    public string $footer_text = '';
    public $default_response_days = 3;
    public $logo = null;
    public bool $remove_logo = false;

    public function mount(): void
    {
        $this->authorizeAction('read', 'settings');

        $l = Letterhead::for(auth()->user()->company_id);

        foreach (['legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'footer_text'] as $f) {
            $this->{$f} = (string) $l->{$f};
        }

        $this->default_response_days = $l->default_response_days;
    }

    public function save(): void
    {
        $this->authorizeAction('update', 'settings');

        $this->validate([
            'legal_name' => ['required', 'string', 'max:150'], 'address' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:60'], 'email' => ['nullable', 'email', 'max:150'], 'website' => ['nullable', 'string', 'max:150'],
            'rccm' => ['nullable', 'string', 'max:80'], 'nif' => ['nullable', 'string', 'max:80'], 'footer_text' => ['nullable', 'string', 'max:255'],
            'default_response_days' => ['required', 'integer', 'between:1,30'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ], [], ['legal_name' => 'nom officiel', 'default_response_days' => 'délai de réponse par défaut', 'logo' => 'logo']);

        $l = Letterhead::for(auth()->user()->company_id);
        $data = $this->only(['legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'footer_text']);
        $data['default_response_days'] = (int) $this->default_response_days;
        $old = $l->logo_path;

        if ($this->logo) {
            $data['logo_path'] = $this->logo->storeAs('companies/'.$l->company_id.'/branding', Str::uuid().'.'.$this->logo->extension(), 'local');
        } elseif ($this->remove_logo) {
            $data['logo_path'] = null;
        }

        $l->update($data);

        if ($old && array_key_exists('logo_path', $data) && $data['logo_path'] !== $old) {
            Storage::disk('local')->delete($old);
        }

        AuditLogger::log('updated', 'settings', $l->id, null, ['letterhead' => true]);

        $this->reset(['logo', 'remove_logo']);
        $this->dispatch('notify', type: 'success', message: 'En-tête enregistré.');
    }

    public function render()
    {
        return view('livewire.discipline.letterhead-page', ['letterhead' => Letterhead::for(auth()->user()->company_id)->refresh()]);
    }
}
