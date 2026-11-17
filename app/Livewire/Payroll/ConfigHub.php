<?php

namespace App\Livewire\Payroll;

use App\Services\Payroll\HubSections;
use App\Services\Payroll\PayrollUiMode;

/** Pôle « Configuration de la paie » : paramètres, démarrage rapide, rubriques, modèles, grilles, bulletin, réglages. */
class ConfigHub extends HubPage
{
    protected function heading(): string
    {
        return 'Configuration de la paie';
    }

    protected function intro(): string
    {
        return 'Ce qui se règle une fois, puis se retouche rarement. Pas besoin de tout remplir : l\'assistant « Démarrer la paie » vous dit par où commencer.';
    }

    protected function sections(): array
    {
        return HubSections::config();
    }

    protected function extra(): array
    {
        return [
            'mode' => PayrollUiMode::mode($this->companyId()),
            'canSwitchMode' => $this->allowed('update', 'payroll_settings'),
        ];
    }

    /** Change le mode d'affichage puis recharge la page : les écrans déjà ouverts se redessinent avec le nouveau mode. */
    public function setMode(string $mode): void
    {
        $this->allow('update', 'payroll_settings');

        $new = PayrollUiMode::normalize($mode);
        abort_if($new === null, 404);

        $old = PayrollUiMode::mode($this->companyId());

        if ($old !== $new) {
            PayrollUiMode::set($this->companyId(), $new);
            $this->audit('update', 'payroll_settings', null, ['ui_mode' => $old], ['ui_mode' => $new]);
        }

        $this->redirectRoute(HubSections::CONFIG);
    }
}
