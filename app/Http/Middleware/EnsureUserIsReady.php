<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 1. Compte désactivé : déconnexion et invalidation de la session
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte a été désactivé. Veuillez contacter un administrateur.',
            ]);
        }

        // 2. Obligation de changement de mot de passe
        if ($user->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
