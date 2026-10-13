<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

    // vérifie le droit sur une ressource
    public function handle(Request $request, Closure $next, string $action, string $resource): Response
    {
        $user = $request->user();

        abort_unless($user && $user->canDo($action, $resource), 403);

        return $next($request);
    }
}
