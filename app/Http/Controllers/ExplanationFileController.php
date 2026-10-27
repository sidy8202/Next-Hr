<?php

namespace App\Http\Controllers;

use App\Models\ExplanationCase;
use App\Models\ExplanationDocument;
use App\Services\ExplanationService;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Lettres du dossier et pièce jointe de la réponse : réservées à l'employé, à l'auteur, à la RH et à qui a le droit de lecture. */
class ExplanationFileController extends Controller
{
    public function letter(ExplanationCase $case, ExplanationDocument $document): StreamedResponse
    {
        return $this->serve($case, $document->case_id === $case->id ? $document->file_path : null, 'lettre-'.$document->reference, false);
    }

    public function letterView(ExplanationCase $case, ExplanationDocument $document): StreamedResponse
    {
        return $this->serve($case, $document->case_id === $case->id ? $document->file_path : null, 'lettre-'.$document->reference, true);
    }

    public function attachment(ExplanationCase $case): StreamedResponse
    {
        return $this->serve($case, $case->response_attachment_path, 'piece-jointe', false);
    }

    public function attachmentView(ExplanationCase $case): StreamedResponse
    {
        return $this->serve($case, $case->response_attachment_path, 'piece-jointe', true);
    }

    private function serve(ExplanationCase $case, ?string $path, string $name, bool $inline): StreamedResponse
    {
        abort_unless(app(ExplanationService::class)->canView(auth()->user(), $case), 403);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $file = $name.'.'.pathinfo($path, PATHINFO_EXTENSION);

        return ($inline && FileViewer::previewable($path))
            ? FileViewer::inline($path, $file)
            : Storage::disk('local')->download($path, $file);
    }
}
