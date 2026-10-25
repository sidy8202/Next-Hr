<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Lettre de motivation jointe à une candidature. */
class ApplicationDocumentController extends Controller
{
    public function download(Application $application): StreamedResponse
    {
        $this->authorizeLetter($application);

        return Storage::disk('local')->download($application->cover_letter_path, $this->name($application));
    }

    public function view(Application $application): StreamedResponse
    {
        $this->authorizeLetter($application);

        return FileViewer::inline($application->cover_letter_path, $this->name($application));
    }

    private function authorizeLetter(Application $application): void
    {
        abort_unless(auth()->user()->canDo('read', 'applications'), 403);
        abort_unless($application->cover_letter_path && Storage::disk('local')->exists($application->cover_letter_path), 404);
    }

    private function name(Application $application): string
    {
        return 'lettre-de-motivation.'.pathinfo($application->cover_letter_path, PATHINFO_EXTENSION);
    }
}
