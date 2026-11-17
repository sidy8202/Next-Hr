<?php

namespace App\Livewire\Payroll;

use App\Services\Payroll\HubSections;

/** Pôle « Paie du mois » : traitement mensuel, variables, prêts, simulation. */
class MonthHub extends HubPage
{
    protected function heading(): string
    {
        return 'Paie du mois';
    }

    protected function intro(): string
    {
        return 'Tout ce qui se fait chaque mois, sur une seule page. Ouvrez une section pour travailler ; les autres restent repliées.';
    }

    protected function sections(): array
    {
        return HubSections::month();
    }
}
