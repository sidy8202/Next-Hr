<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use Livewire\Component;

/**
 * Base des pôles de la paie : une page, des sections en accordéon. Chaque section embarque un écran existant,
 * chargé seulement à sa première ouverture (le serveur retient les sections déjà chargées dans $loaded).
 * L'ouverture d'une section par son ancre (#items) est gérée côté navigateur, voir la vue hub.blade.php.
 */
abstract class HubPage extends Component
{
    use PayrollGuards;

    /** Clés des sections déjà chargées. @var array<int, string> */
    public array $loaded = [];

    public string $flash = '';

    abstract protected function heading(): string;

    abstract protected function intro(): string;

    /** @return array<int, array{key: string, title: string, hint: string, alias: string, resource: string}> */
    abstract protected function sections(): array;

    /** Données supplémentaires de la vue (le pôle « configuration » ajoute le mode d'affichage). @return array<string, mixed> */
    protected function extra(): array
    {
        return ['mode' => null, 'canSwitchMode' => false];
    }

    /** @return array<int, array{key: string, title: string, hint: string, alias: string, resource: string}> sections que l'utilisateur a le droit de lire */
    protected function visibleSections(): array
    {
        return array_values(array_filter($this->sections(), fn ($s) => $this->allowed('read', $s['resource'])));
    }

    public function mount(): void
    {
        $visible = $this->visibleSections();

        abort_if($visible === [], 403);

        // La première section est prête dès l'affichage ; les autres se chargent à l'ouverture.
        $this->loaded = [$visible[0]['key']];
    }

    /** Appelé par le navigateur à l'ouverture d'une section. */
    public function load(string $key): void
    {
        foreach ($this->visibleSections() as $s) {
            if ($s['key'] === $key) {
                if (! in_array($key, $this->loaded, true)) {
                    $this->loaded[] = $key;
                }

                return;
            }
        }
    }

    public function render()
    {
        $sections = $this->visibleSections();

        $rows = [];

        foreach ($sections as $i => $s) {
            $rows[] = $s + ['first' => $i === 0, 'is_loaded' => in_array($s['key'], $this->loaded, true)];
        }

        return view('livewire.payroll.hub', [
            'intro' => $this->intro(),
            'sections' => $rows,
            'keys' => array_column($sections, 'key'),
        ] + $this->extra())->layout($this->resolveLayout('company'), ['title' => $this->heading()]);
    }
}
