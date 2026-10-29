<?php

namespace App\Services;

use App\Models\Letterhead;
use App\Models\Mission;
use App\Models\MissionParticipant;
use Carbon\CarbonInterface;

/**
 * Rend l'ordre de mission d'UN participant. Aucun montant n'est imprimé.
 * Retourne [contenu, extension] : PDF si une bibliothèque PDF est installée, sinon HTML (comme tes lettres).
 * Si ExplanationService utilise une autre bibliothèque PDF, adapte uniquement toPdf().
 */
class MissionOrderRenderer
{
    public function render(Mission $mission, MissionParticipant $p, string $reference, Letterhead $letterhead, array $signatories, CarbonInterface $date): array
    {
        $e = $p->employee;
        $period = $mission->start_date->format('d/m/Y').' au '.$mission->end_date->format('d/m/Y');

        $details = array_filter([
            'Mission' => $mission->reference,
            'Destination' => $mission->destination_zone,
            'Période' => 'Du '.$period,
            'Fonction dans la mission' => $p->role->label(),
            'Hébergement' => $mission->lodging,
        ]);

        $html = view('pdf.mission-order', [
            'letterhead' => $letterhead,
            'reference' => $reference,
            'date' => $date,
            'recipient' => array_values(array_filter([$e->full_name, 'Matricule : '.$e->matricule, $e->position?->title, $p->department?->name])),
            'subject' => 'Mission à '.$mission->destination_zone,
            'paragraphs' => [
                "Il est ordonné à {$e->full_name}, matricule {$e->matricule}".($e->position?->title ? ", {$e->position->title}" : '').", de se rendre en mission à {$mission->destination_zone}, du {$period}.",
                "Objet de la mission : {$mission->purpose}.",
                'Les autorités civiles, militaires et administratives sont priées de bien vouloir faciliter l’accomplissement de cette mission et de prêter, en cas de besoin, aide et assistance au porteur du présent ordre.',
            ],
            'details' => $details,
            'signatories' => $signatories,
        ])->render();

        $pdf = $this->toPdf($html);

        return $pdf !== null ? [$pdf, 'pdf'] : [$html, 'html'];
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
