<?php

namespace App\Http\Controllers;

use App\Services\Payroll\HubSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Les anciennes pages de la paie (une page par écran) redirigent vers leur pôle, section ouverte.
 * Les liens enregistrés et les liens internes (route('payroll.salaries'), etc.) continuent de marcher ; la chaîne de requête
 * est conservée (par exemple ?payslip=12 pour la simulation d'un bulletin).
 * La correspondance se trouve dans HubSections::redirects() : aucune option de route à écrire.
 */
class PayrollHubRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $target = HubSections::redirectFor((string) $request->route()?->getName());

        abort_if($target === null, 404);

        $query = $request->getQueryString();

        return redirect(route($target['route']) . ($query ? '?' . $query : '') . '#' . $target['anchor']);
    }
}
