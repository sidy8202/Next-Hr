<?php

namespace App\Livewire\Payroll\Concerns;

/**
 * Petits outils communs aux écrans de paie : contrôle des droits, journal d'audit, lecture de montants, nom d'employé.
 * Ne dépend que de User::canDo($action, $resource), qui existe déjà dans le projet.
 */
trait PayrollGuards
{
    /** Refuse (403) si l'utilisateur n'a pas le droit. */
    protected function allow(string $action, string $resource): void
    {
        abort_unless(auth()->user()?->canDo($action, $resource), 403);
    }

    protected function allowed(string $action, string $resource): bool
    {
        return (bool) auth()->user()?->canDo($action, $resource);
    }

    protected function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    /** Écrit dans le journal d'audit existant (AuditLogger::log). Ne bloque jamais l'écran si le journal est indisponible. */
    protected function audit(string $action, string $resource, ?int $id, mixed $old = null, mixed $new = null): void
    {
        foreach (['App\\Services\\AuditLogger', 'App\\Support\\AuditLogger', 'App\\Audit\\AuditLogger', 'App\\AuditLogger'] as $class) {
            if (class_exists($class)) {
                try {
                    $class::log($action, $resource, $id, $old, $new);
                } catch (\Throwable) {
                    // le journal ne doit jamais empêcher d'enregistrer
                }

                return;
            }
        }
    }

    /** Ne garde que les chiffres : « 1 250 000 FCFA » devient 1250000. */
    protected function digits(mixed $value): int
    {
        return (int) preg_replace('/\D/', '', (string) $value);
    }

    /** Nom affichable d'un employé, sans supposer le nom exact des colonnes. */
    protected function employeeName(object $employee): string
    {
        $attrs = method_exists($employee, 'getAttributes') ? $employee->getAttributes() : (array) $employee;

        foreach (['full_name', 'name'] as $key) {
            if (! empty($attrs[$key])) {
                return (string) $attrs[$key];
            }
        }

        $name = trim(($attrs['first_name'] ?? '') . ' ' . ($attrs['last_name'] ?? ''));

        return $name !== '' ? $name : '#' . ($attrs['id'] ?? '?');
    }

    /**
     * Trouve le layout Blade du projet (Livewire 4 cherche sinon « layouts::app », qui n'existe pas ici).
     * $kind : 'company' (espace entreprise) ou 'platform' (espace plateforme).
     */
    protected function resolveLayout(string $kind): string
    {
        $candidates = $kind === 'platform'
            ? ['components.layouts.platform', 'layouts.platform', 'components.platform', 'components.layouts.plateforme']
            : ['components.layouts.app', 'layouts.app', 'components.layouts.company', 'components.layouts.main', 'components.app-layout', 'components.layouts.dashboard'];

        foreach ($candidates as $view) {
            if (view()->exists($view)) {
                return $view;
            }
        }

        // Dernier recours : on parcourt les dossiers de layouts et on reconnaît celui de la plateforme à son texte.
        $ignored = ['guest', 'auth', 'login', 'public', 'careers', 'pdf', 'print', 'mail', 'letterhead'];

        foreach (['components/layouts', 'layouts'] as $dir) {
            foreach (glob(resource_path("views/$dir/*.blade.php")) ?: [] as $file) {
                $base = basename($file, '.blade.php');
                $isPlatform = str_contains((string) file_get_contents($file), 'Espace plateforme');

                if (($kind === 'platform') === $isPlatform && ! in_array($base, $ignored, true)) {
                    return str_replace('/', '.', $dir) . '.' . $base;
                }
            }
        }

        throw new \RuntimeException("Layout « $kind » introuvable : indiquez son chemin dans PayrollGuards::resolveLayout().");
    }
}
