<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateCvController extends Controller
{
    /** Téléchargement protégé : droit de lecture + candidat de MON entreprise (la liaison de route est filtrée). */
    public function __invoke(Candidate $candidate): StreamedResponse
    {
        $this->authorizeCv($candidate);

        return Storage::disk('local')->download($candidate->cv_path, $this->name($candidate));
    }

    /** Affichage dans le navigateur (PDF, images). Les CV Word se téléchargent. */
    public function view(Candidate $candidate): StreamedResponse
    {
        $this->authorizeCv($candidate);

        return FileViewer::inline($candidate->cv_path, $this->name($candidate));
    }

    private function authorizeCv(Candidate $candidate): void
    {
        abort_unless(auth()->user()->canDo('read', 'candidates'), 403);
        abort_unless($candidate->cv_path && Storage::disk('local')->exists($candidate->cv_path), 404);
    }

    private function name(Candidate $candidate): string
    {
        return $candidate->cv_original_name ?: 'cv.'.pathinfo($candidate->cv_path, PATHINFO_EXTENSION);
    }
}
