<?php

namespace App\Http\Controllers;

use App\Models\PlatformProfile;
use App\Models\SubscriptionPayment;
use App\Services\InvoiceRenderer;
use Symfony\Component\HttpFoundation\Response;

/** Facture d'un paiement d'abonnement, générée à la demande (aperçu ou téléchargement avec ?dl=1). */
class PlatformInvoiceController extends Controller
{
    public function show(SubscriptionPayment $payment): Response
    {
        abort_unless(auth()->user()?->platformCan('subscriptions.view'), 403);

        [$content, $ext, $type] = app(InvoiceRenderer::class)->render($payment, PlatformProfile::current());

        $name = 'facture-'.$payment->invoice_number.'.'.$ext;
        $disposition = request()->boolean('dl') ? 'attachment' : 'inline';

        return response($content, 200, ['Content-Type' => $type, 'Content-Disposition' => $disposition.'; filename="'.$name.'"']);
    }
}
