<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Support\Facades\Storage;

class CandidateCvController extends Controller
{
    /** Téléchargement protégé : droit de lecture + candidat de MON entreprise (la liaison de route est filtrée). */
    public function __invoke(Candidate $candidate)
    {
        abort_unless(auth()->user()->canDo('read', 'candidates'), 403);
        abort_unless($candidate->cv_path && Storage::disk('local')->exists($candidate->cv_path), 404);

        $name = $candidate->cv_original_name ?: 'cv.'.pathinfo($candidate->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($candidate->cv_path, $name);
    }
}
