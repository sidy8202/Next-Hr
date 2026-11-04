<?php

namespace App\Services;

use App\Models\PlatformProfile;
use App\Models\SubscriptionPayment;

/**
 * Facture d'un paiement d'abonnement. Retourne [contenu, extension, type MIME] :
 * PDF si une bibliothèque PDF est installée, sinon HTML (même comportement que tes lettres).
 */
class InvoiceRenderer
{
    public function render(SubscriptionPayment $payment, PlatformProfile $profile): array
    {
        $payment->loadMissing(['subscription.plan', 'company']);

        $currency = $payment->company?->currency ?: 'XOF';

        $html = view('pdf.subscription-invoice', [
            'payment' => $payment, 'sub' => $payment->subscription, 'plan' => $payment->subscription?->plan, 'company' => $payment->company,
            'profile' => $profile, 'currency' => in_array($currency, ['XOF', 'XAF'], true) ? 'FCFA' : $currency,
        ])->render();

        $pdf = $this->toPdf($html);

        return $pdf !== null ? [$pdf, 'pdf', 'application/pdf'] : [$html, 'html', 'text/html; charset=UTF-8'];
    }

    private function toPdf(string $html): ?string
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output();
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output();
        }

        return null;
    }
}
