<?php

namespace App\Livewire\Payroll;

use App\Services\Payroll\HubSections;

/** Pôle « Employés et salaires » : salaires, affectation des modèles, primes attribuées. */
class PeopleHub extends HubPage
{
    protected function heading(): string
    {
        return 'Employés et salaires';
    }

    protected function intro(): string
    {
        return 'Ce qui est propre à chaque employé : son salaire, son modèle de traitement et ses primes.';
    }

    protected function sections(): array
    {
        return HubSections::people();
    }
}
