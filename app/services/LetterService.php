<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExplanationCase;
use App\Models\ExplanationDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Génère une lettre officielle (PDF si dompdf est installé, sinon page HTML imprimable), la range en privé et l'attache au dossier. */
class LetterService
{
    private const TITLES = ['request' => 'Demande d’explication', 'response' => 'Réponse à la demande d’explication', 'decision' => 'Décision'];

    /** @param array<string, mixed> $data données de la vue pdf.letter */
    public function generate(ExplanationCase $case, string $kind, ?User $by, array $data): ExplanationDocument
    {
        $html = view('pdf.letter', $data)->render();

        $pdf = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
        $content = $pdf ? \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output() : $html;
        $path = "companies/{$case->company_id}/discipline/".Str::uuid().($pdf ? '.pdf' : '.html');

        Storage::disk('local')->put($path, $content);

        // Versé aussi au dossier de l'employé (type confidentiel : visible selon les droits sur les documents confidentiels)
        $document = Document::create([
            'employee_id' => $case->employee_id,
            'type' => "explanation_{$kind}",
            'title' => self::TITLES[$kind].' — réf. '.$data['reference'],
            'file_path' => $path,
            'uploaded_by' => $by?->id,
        ]);

        return ExplanationDocument::create([
            'case_id' => $case->id,
            'kind' => $kind,
            'reference' => $data['reference'],
            'file_path' => $path,
            'document_id' => $document->id,
            'created_by' => $by?->id,
        ]);
    }
}
