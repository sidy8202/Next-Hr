<?php

namespace App\Http\Controllers;

use App\Models\PlatformProfile;
use App\Models\SubscriptionPayment;
use App\Services\InvoiceRenderer;
use Symfony\Component\HttpFoundation\Response;

/** Facture d'un paiement, côté entreprise : uniquement les factures de SA société, et seulement pour son propriétaire. */
class CompanyInvoiceController extends Controller
{
    public function show(SubscriptionPayment $payment): Response
    {
        $u = auth()->user();

        abort_unless($u?->company_id && (int) $payment->company_id === (int) $u->company_id, 404);
        abort_unless($u->is_company_owner || $u->canDo('read', 'settings'), 403);

        [$content, $ext, $type] = app(InvoiceRenderer::class)->render($payment, PlatformProfile::current());

        $name = 'facture-'.$payment->invoice_number.'.'.$ext;
        $disposition = request()->boolean('dl') ? 'attachment' : 'inline';

        return response($content, 200, ['Content-Type' => $type, 'Content-Disposition' => $disposition.'; filename="'.$name.'"']);
    }
}
