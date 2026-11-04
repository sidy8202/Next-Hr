<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Applique la formule d'abonnement : quelles fonctionnalités, quels plafonds.
 * - Une entreprise SANS abonnement n'est pas restreinte (elle garde tout, c'est le cas de tes entreprises existantes).
 * - Une ressource qui n'appartient à aucune fonctionnalité de config/plans.php est « socle » : toujours disponible.
 * - L'équipe plateforme (pas d'entreprise) n'est jamais restreinte.
 */
class PlanGate
{
    private static ?array $resourceMap = null;

    /** États : unrestricted (sans abonnement), ok, grace (retard de paiement), locked (suspendu, expiré, annulé). */
    public static function profile(Company|int $company): array
    {
        $id = $company instanceof Company ? $company->id : $company;

        return Cache::remember(self::key($id), 300, function () use ($id) {
            $sub = Subscription::where('company_id', $id)->latest('id')->first();

            if (! $sub) {
                return ['state' => 'unrestricted', 'plan' => null, 'features' => null, 'limits' => []];
            }

            $plan = Plan::with('features')->find($sub->plan_id);
            $suspended = Company::whereKey($id)->value('status') === 'suspended';

            return [
                'state' => $suspended ? 'locked' : match ($sub->status) {
                    'trial', 'pending', 'active' => 'ok',
                    'past_due' => 'grace',
                    default => 'locked',
                },
                'plan' => $plan?->code,
                'features' => $plan ? $plan->features->where('enabled', true)->pluck('feature')->all() : [],
                'limits' => $plan ? ['max_employees' => $plan->max_employees, 'storage_gb' => $plan->storage_gb, 'audit_retention_months' => $plan->audit_retention_months] : [],
            ];
        });
    }

    public static function allowsResource(User $user, string $resource): bool
    {
        if (! $user->company_id) {
            return true;
        }

        $p = self::profile((int) $user->company_id);

        if ($p['state'] === 'unrestricted') {
            return true;
        }

        if ($p['state'] === 'locked') {
            return false;
        }

        $feature = self::resourceMap()[$resource] ?? null;

        return $feature === null || in_array($feature, $p['features'], true);
    }

    public static function allowsFeature(Company|int $company, string $feature): bool
    {
        $p = self::profile($company);

        return match ($p['state']) {
            'unrestricted' => true,
            'locked' => false,
            default => in_array($feature, $p['features'], true),
        };
    }

    public static function limit(Company|int $company, string $key): ?int
    {
        return self::profile($company)['limits'][$key] ?? null;
    }

    /** Peut-on ajouter $adding employés sans dépasser le plafond de la formule ? */
    public static function canAddEmployees(Company|int $company, int $adding = 1): bool
    {
        $max = self::limit($company, 'max_employees');

        if ($max === null) {
            return true;
        }

        $id = $company instanceof Company ? $company->id : $company;
        $current = \App\Models\Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('company_id', $id)->where('status', 'active')->count();

        return $current + $adding <= $max;
    }

    public static function flush(int $companyId): void
    {
        Cache::forget(self::key($companyId));
    }

    /** Après une modification de la matrice : invalide toutes les entreprises. */
    public static function flushAll(): void
    {
        Cache::forever('plan_gate_version', (int) Cache::get('plan_gate_version', 1) + 1);
    }

    private static function key(int $companyId): string
    {
        return 'plan_gate.v'.Cache::get('plan_gate_version', 1).'.'.$companyId;
    }

    private static function resourceMap(): array
    {
        if (self::$resourceMap === null) {
            self::$resourceMap = [];

            foreach (config('plans.features') as $key => $f) {
                foreach ($f['resources'] as $resource) {
                    self::$resourceMap[$resource] = $key;
                }
            }
        }

        return self::$resourceMap;
    }
}
