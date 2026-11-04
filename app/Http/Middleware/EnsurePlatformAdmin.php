<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Réserve l'espace plateforme aux comptes de l'équipe plateforme actifs. */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_platform_admin && $user->is_active, 403);

        return $next($request);
    }
}
