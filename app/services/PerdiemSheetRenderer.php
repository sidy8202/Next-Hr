<?php

namespace App\Services;

use App\Models\Letterhead;
use App\Models\Mission;
use App\Models\MissionParticipant;
use Illuminate\Support\Collection;

/**
 * Fiche de perdiem : document SÉPARÉ de l'ordre de mission, qui porte les montants.
 * Elle reste à la Finance et à l'employé ; elle ne voyage pas avec l'ordre.
 * Retourne [contenu, extension, type MIME].
 */
class PerdiemSheetRenderer
{
    public function render(Mission $mission, MissionParticipant $p, Collection $payments, Letterhead $letterhead): array
    {
        $html = view('pdf.perdiem-sheet', [
            'mission' => $mission, 'participant' => $p, 'payments' => $payments, 'letterhead' => $letterhead,
            'summary' => PaymentPlanner::summarize(
                (int) $p->total_amount,
                (int) $payments->where('status', 'paid')->sum('paid_amount'),
                $payments->where('status', 'planned')->count(),
            ),
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
