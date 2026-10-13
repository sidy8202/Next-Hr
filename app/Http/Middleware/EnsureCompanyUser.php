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

        return $next($request);
    }
}
