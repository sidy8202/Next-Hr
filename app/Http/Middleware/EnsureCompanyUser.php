<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si l'utilisateur est administrateur de la plateforme (aucun company_id), redirection vers /platform
        if ($user && ! $user->company_id) {
            return redirect()->route('platform');
        }
        if (\App\Services\PlanGate::profile($user->company_id)['state'] === 'locked'
            && ! $request->routeIs('subscription.*', 'logout', 'notifications.*', 'password.*')) {
            return $user->is_company_owner
                ? redirect()->route('subscription.mine')
                : abort(403, 'Votre abonnement est suspendu ou expiré. Contactez le propriétaire du compte.');
        }

        return $next($request);
    }
}
